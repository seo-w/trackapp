<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AppSettingsRepository;
use App\Services\MerkawebService;
use App\Support\Crypt;

final class SettingsController extends Controller
{
    public function index(): void
    {
        /** @var array<string, string> $errors */
        $errors = flash_take('config_errors', []);
        if (! is_array($errors)) {
            $errors = [];
        }

        $oldBag = session()->get('_old_input', []);
        session()->remove('_old_input');
        /** @var array<string, mixed> $old */
        $old = is_array($oldBag) ? $oldBag : [];

        $flashSuccess = flash_take('config_flash_success');
        $flashError = flash_take('config_flash_error');

        try {
            $repo = new AppSettingsRepository(db()->pdo());
            $row = $repo->firstOrCreateEmpty();
        } catch (\Throwable) {
            $this->view('configuracion/index', [
                'title' => 'Configuración',
                'heading' => 'Conexión al API',
                'dbUnavailable' => true,
                'errors' => [],
                'api_base_url' => '',
                'tienda_id' => '',
                'has_stored_token' => false,
                'flashSuccess' => $flashSuccess,
                'flashError' => $flashError ?: 'La base de datos no está disponible. Activa DB_ENABLED, configura DB_* y ejecuta php database/migrate.php.',
            ]);

            return;
        }

        $apiDefault = is_string($old['api_base_url'] ?? null) ? $old['api_base_url'] : (string) $row['api_base_url'];
        $tiendaDefault = is_string($old['tienda_id'] ?? null) ? $old['tienda_id'] : (string) $row['tienda_id'];

        $this->view('configuracion/index', [
            'title' => 'Configuración',
            'heading' => 'Conexión al API',
            'dbUnavailable' => false,
            'errors' => $errors,
            'api_base_url' => $apiDefault,
            'tienda_id' => $tiendaDefault,
            'has_stored_token' => $row['access_token_encrypted'] !== null && $row['access_token_encrypted'] !== '',
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
        ]);
    }

    public function save(): void
    {
        if (! csrf_validate()) {
            flash('config_flash_error', 'El token de seguridad del formulario no es válido. Vuelve a cargar la página e inténtalo de nuevo.');
            redirect('/configuracion');
        }

        $apiRaw = trim((string) ($_POST['api_base_url'] ?? ''));
        $tienda = trim((string) ($_POST['tienda_id'] ?? ''));
        $tokenInput = (string) ($_POST['access_token'] ?? '');
        $tokenTrim = trim($tokenInput);

        $errors = $this->validate($apiRaw, $tienda);

        if ($errors !== []) {
            session()->set('_old_input', [
                'api_base_url' => $apiRaw,
                'tienda_id' => $tienda,
            ]);
            flash('config_errors', $errors);
            redirect('/configuracion');
        }

        $apiNormalized = $this->normalizeApiBaseUrl($apiRaw);

        try {
            $repo = new AppSettingsRepository(db()->pdo());
            $row = $repo->firstOrCreateEmpty();
        } catch (\Throwable) {
            flash('config_flash_error', 'No hay conexión a la base de datos. Revisa DB_ENABLED y las credenciales.');
            redirect('/configuracion');
        }

        $encrypted = $row['access_token_encrypted'];
        if ($tokenTrim !== '') {
            try {
                $crypt = Crypt::fromAppConfig(config('app'));
                $encrypted = $crypt->encrypt($tokenTrim);
            } catch (\RuntimeException) {
                flash('config_flash_error', 'Falta APP_KEY (o app.key). Define una clave larga y aleatoria en .env o en config/app.php antes de guardar el token.');
                session()->set('_old_input', [
                    'api_base_url' => $apiRaw,
                    'tienda_id' => $tienda,
                ]);
                redirect('/configuracion');
            } catch (\Throwable) {
                flash('config_flash_error', 'No se pudo cifrar el token de acceso. Comprueba la extensión openssl.');
                session()->set('_old_input', [
                    'api_base_url' => $apiRaw,
                    'tienda_id' => $tienda,
                ]);
                redirect('/configuracion');
            }
        }

        try {
            $repo->update((int) $row['id'], [
                'api_base_url' => $apiNormalized,
                'tienda_id' => $tienda,
                'access_token_encrypted' => $encrypted,
            ]);
            flash('config_flash_success', 'Configuración del API guardada correctamente.');
        } catch (\Throwable) {
            flash('config_flash_error', 'No se pudo guardar en base de datos.');
        }

        redirect('/configuracion');
    }

    public function testConnection(): void
    {
        if (! csrf_validate()) {
            flash('config_flash_error', 'El token de seguridad del formulario no es válido. Vuelve a cargar la página e inténtalo de nuevo.');
            redirect('/configuracion');
        }

        try {
            $repo = new AppSettingsRepository(db()->pdo());
            $service = MerkawebService::fromApp(config('app'), $repo);
            $result = $service->testConnection();
        } catch (\Throwable $e) {
            flash('config_flash_error', $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo ejecutar la prueba de conexión.');
            redirect('/configuracion');
        }

        if ($result->ok) {
            flash('config_flash_success', $result->message);
        } else {
            flash('config_flash_error', $result->message);
        }

        redirect('/configuracion');
    }

    /**
     * @return array<string, string>
     */
    private function validate(string $apiBaseUrl, string $tiendaId): array
    {
        $errors = [];

        if ($apiBaseUrl === '') {
            $errors['api_base_url'] = 'La URL base del API es obligatoria.';
        } elseif (strlen($apiBaseUrl) > 512) {
            $errors['api_base_url'] = 'La URL no puede superar 512 caracteres.';
        } elseif (filter_var($apiBaseUrl, FILTER_VALIDATE_URL) === false) {
            $errors['api_base_url'] = 'Introduce una URL válida (por ejemplo https://api.ejemplo.com).';
        } else {
            $parts = parse_url($apiBaseUrl);
            $scheme = is_array($parts) ? ($parts['scheme'] ?? '') : '';
            if (! in_array($scheme, ['http', 'https'], true)) {
                $errors['api_base_url'] = 'Usa http:// o https:// en la URL base.';
            }
        }

        if ($tiendaId === '') {
            $errors['tienda_id'] = 'El identificador de tienda es obligatorio.';
        } elseif (strlen($tiendaId) > 191) {
            $errors['tienda_id'] = 'El identificador no puede superar 191 caracteres.';
        } elseif (preg_match('/[\s\x00-\x1F\x7F]/', $tiendaId)) {
            $errors['tienda_id'] = 'Quita espacios y caracteres de control del identificador.';
        }

        return $errors;
    }

    private function normalizeApiBaseUrl(string $url): string
    {
        return rtrim(trim($url), '/');
    }
}
