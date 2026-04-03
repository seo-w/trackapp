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

            // 4. Crear sesión local segura
            session()->regenerate();
            session()->set('user_id', (string) ($userData['id'] ?? 'unknown'));
            session()->set('user_email', $email);
            session()->set('user_name', (string) ($userData['name'] ?? 'Usuario'));
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
}
