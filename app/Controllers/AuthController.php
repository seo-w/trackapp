<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AppSettingsRepository;
use App\Services\MerkawebService;
use App\Support\Crypt;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (session()->has('user_id')) {
            redirect('/');
        }

        $this->view('auth/login', [
            'title' => 'Acceso de Seguridad',
            'flashError' => flash_take('auth_error'),
            'flashSuccess' => flash_take('auth_success'),
        ]);
    }

    public function login(): void
    {
        if (! csrf_validate()) {
            flash('auth_error', 'Token CSRF inválido.');
            redirect('/login');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            flash('auth_error', 'Email y contraseña son obligatorios.');
            redirect('/login');
        }

        try {
            $pdo = db()->pdo();
            $repo = new AppSettingsRepository($pdo);
            $service = MerkawebService::fromApp(config('app'), $repo);
            
            // 1. Intentar login contra Merkaweb (usando su API como validador de credenciales)
            $loginRes = $service->login($email, $password);
            
            if (!$loginRes->ok) {
                flash('auth_error', 'Credenciales incorrectas: ' . $loginRes->message);
                redirect('/login');
            }

            // 2. Extraer datos del usuario y token
            $userData = $loginRes->data['data'] ?? [];
            $token = (string) ($loginRes->data['token'] ?? '');

            if ($token === '') {
                flash('auth_error', 'No se recibió un token válido de Merkaweb.');
                redirect('/login');
            }

            // 3. Obtener Info Extendida (Tienda)
            $infoRes = $service->getInfoUser($token);
            $tienda = null;
            if ($infoRes->ok) {
                $tienda = $infoRes->data['data']['tienda'] ?? null;
            }

            // 4. Buscar o Crear usuario local para control de permisos
            $userRepo = new \App\Repositories\UserRepository($pdo);
            $localUser = $userRepo->findByEmail($email);

            if (! $localUser) {
                // Si no existe, lo creamos. 
                // Por defecto: el primer usuario de la DB es ADMIN y APROBADO.
                // Los demás son USER y NO APROBADOS (requieren aceptación).
                $allUsers = $userRepo->all();
                $isFirst = count($allUsers) === 0;
                
                $userRepo->create([
                    'email' => $email,
                    'password_hash' => 'EXTERNAL_MERKAWEB', // No usamos pass local si entra por API
                    'role' => $isFirst ? 'admin' : 'user',
                    'is_approved' => $isFirst ? 1 : 0,
                    'tienda_id' => (string) ($tienda['id'] ?? ''),
                    'tienda_name' => (string) ($tienda['tienda_name'] ?? ''),
                ]);
                $localUser = $userRepo->findByEmail($email);
            } else {
                // Actualizar info de tienda por si ha cambiado
                $userRepo->update((int) $localUser['id'], [
                    'tienda_id' => (string) ($tienda['id'] ?? $localUser['tienda_id']),
                    'tienda_name' => (string) ($tienda['tienda_name'] ?? $localUser['tienda_name']),
                ]);
                $localUser = $userRepo->findByEmail($email);
            }

            // 5. Crear sesión local segura
            session()->regenerate();
            session()->set('user_id', (string) ($userData['id'] ?? 'unknown'));
            session()->set('local_user_id', (int) ($localUser['id'] ?? 0));
            session()->set('user_email', $email);
            session()->set('user_name', (string) ($userData['name'] ?? 'Usuario'));
            session()->set('user_role', (string) ($localUser['role'] ?? 'user'));
            session()->set('user_approved', (bool) ($localUser['is_approved'] ?? false));
            session()->set('merkaweb_token', $token);
            
            if ($tienda) {
                session()->set('tienda_id', (string) ($tienda['id'] ?? ''));
                session()->set('tienda_name', (string) ($tienda['tienda_name'] ?? ''));
            }

            // Opcional: Actualizar configuracion global con este token si es la primera vez o si quieres que se auto-configure
            $row = $repo->firstOrCreateEmpty();
            if (empty($row['access_token_encrypted'])) {
                $crypt = Crypt::fromAppConfig(config('app'));
                $repo->update((int) $row['id'], [
                    'api_base_url' => $row['api_base_url'] ?: 'https://api.merkaweb7.com/api/v1',
                    'tienda_id' => (string) ($tienda['id'] ?? $row['tienda_id']),
                    'tienda_name' => (string) ($tienda['tienda_name'] ?? $row['tienda_name']),
                    'access_token_encrypted' => $crypt->encrypt($token)
                ]);
            }

            flash('auth_success', '¡Bienvenido de nuevo!');
            redirect('/');
            
        } catch (\Throwable $e) {
            flash('auth_error', 'Error del sistema: ' . $e->getMessage());
            redirect('/login');
        }
    }

    public function logout(): void
    {
        session()->remove('user_id');
        session()->remove('user_email');
        session()->remove('user_name');
        session()->remove('merkaweb_token');
        session()->regenerate(true);
        
        flash('auth_success', 'Has cerrado sesión correctamente.');
        redirect('/login');
    }

    public function showWaitingApproval(): void
    {
        if (! session()->has('user_id')) {
            redirect('/login');
        }

        if (session()->get('user_approved', false)) {
            redirect('/');
        }

        $this->view('auth/waiting_approval', [
            'title' => 'Esperando Aprobación',
            'user_email' => session()->get('user_email')
        ]);
    }

    /**
     * Pantalla de recuperación avanzada para administradores.
     */
    public function showRecovery(): void
    {
        $this->view('auth/recovery', [
            'title' => 'Recuperación de Admin',
            'flashError' => flash_take('recovery_error'),
            'flashSuccess' => flash_take('recovery_success'),
            'step' => session()->get('recovery_step', 1),
            'email' => session()->get('recovery_email', ''),
        ]);
    }

    /**
     * Verifica email y token físico.
     */
    public function verifyRecovery(): void
    {
        if (! csrf_validate()) {
            flash('recovery_error', 'Token de seguridad inválido.');
            redirect('/recovery');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $token = trim((string) ($_POST['token'] ?? ''));

        if ($email === '' || $token === '') {
            flash('recovery_error', 'Completa todos los campos obligatorios.');
            redirect('/recovery');
        }

        $pdo = db()->pdo();
        $userRepo = new \App\Repositories\UserRepository($pdo);
        $user = $userRepo->findByEmail($email);

        // Seguridad: El usuario debe existir y ser administrador
        if (! $user || ($user['role'] !== 'admin' && $user['role'] !== 'superadmin')) {
             flash('recovery_error', 'Acceso denegado (Email no autorizado).');
             redirect('/recovery');
        }

        $tokenService = new \App\Services\AdminTokenService();
        if ($tokenService->verify($email, $token)) {
             // Paso 2: Permitir resetear password
             session()->set('recovery_step', 2);
             session()->set('recovery_email', $email);
             session()->set('recovery_verified_token', $token);
             redirect('/recovery');
        } else {
             flash('recovery_error', 'Token de seguridad inválido o no encontrado en el servidor.');
             redirect('/recovery');
        }
    }

    /**
     * Procesa el cambio de contraseña tras la verificación.
     */
    public function resetPassword(): void
    {
        if (! csrf_validate()) {
            flash('recovery_error', 'Token de seguridad inválido.');
            redirect('/recovery');
        }

        $email = session()->get('recovery_email');
        $oldToken = session()->get('recovery_verified_token');
        $pass = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if ($pass === '' || $pass !== $confirm) {
             flash('recovery_error', 'Las contraseñas no coinciden o están vacías.');
             redirect('/recovery');
        }

        if (! $email || ! $oldToken) {
             flash('recovery_error', 'Sesión de recuperación expirada.');
             redirect('/recovery');
        }

        try {
            $pdo = db()->pdo();
            $userRepo = new \App\Repositories\UserRepository($pdo);
            $user = $userRepo->findByEmail($email);
            
            if ($user) {
                // Actualizar password local (hash)
                $userRepo->update((int) $user['id'], [
                    'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
                ]);

                // Regenerar el token físico automáticamente por seguridad
                $tokenService = new \App\Services\AdminTokenService();
                $tokenService->generateForEmail($email);

                // Limpiar sesión de recuperación
                session()->remove('recovery_step');
                session()->remove('recovery_email');
                session()->remove('recovery_verified_token');

                flash('auth_success', 'Contraseña administrativa actualizada correctamente. Inicia sesión normalmente.');
                redirect('/login');
            }
        } catch (\Throwable $e) {
            flash('recovery_error', 'Error al actualizar contrasella: ' . $e->getMessage());
            redirect('/recovery');
        }
    }
}
