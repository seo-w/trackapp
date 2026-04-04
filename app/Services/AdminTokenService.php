<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Gestión de tokens de acceso físicos para administradores.
 * Los tokens se guardan en archivos en el servidor fuera de la carpeta pública.
 */
final class AdminTokenService
{
    private string $tokensPath;

    public function __construct(?string $tokensPath = null)
    {
        // Por defecto: /storage/secure_tokens/
        $this->tokensPath = $tokensPath ?? BASE_PATH . '/storage/secure_tokens';
        
        if (! is_dir($this->tokensPath)) {
            mkdir($this->tokensPath, 0700, true);
        }
    }

    /**
     * Genera un nuevo token y lo guarda en el archivo correspondiente al email.
     */
    public function generateForEmail(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $filePath = $this->getFilePath($email);

        // Guardar con permisos restrictivos (solo el usuario del servidor)
        if (file_put_contents($filePath, $token) === false) {
             throw new \RuntimeException("No se pudo escribir el token en $filePath");
        }
        
        chmod($filePath, 0600);

        return $token;
    }

    /**
     * Verifica que un token enviado coincida con el guardado en el archivo.
     * Implementa protección contra timing attacks usando hash_equals.
     */
    public function verify(string $email, string $providedToken): bool
    {
        $filePath = $this->getFilePath($email);

        if (! is_file($filePath)) {
            return false;
        }

        $storedToken = trim((string) file_get_contents($filePath));
        
        if ($storedToken === '') {
            return false;
        }

        // hash_equals es vital para prevenir ataques de tiempo
        return hash_equals($storedToken, trim($providedToken));
    }

    /**
     * Elimina el archivo del token (p. ej. tras usarlo, si se desea).
     */
    public function burn(string $email): void
    {
        $filePath = $this->getFilePath($email);
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Devuelve la ruta absoluta al archivo basándose en el email (codificado).
     */
    private function getFilePath(string $email): string
    {
        // Codificar el email para evitar path traversal y caracteres inválidos
        // Se usa sha1 + email original saneado si se prefiere, 
        // pero solo sha1 es excelente para nombres de archivos opacos.
        $safeName = sha1(strtolower(trim($email)));
        
        // El basename asegura que no haya inyección de ruta (../../)
        return $this->tokensPath . DIRECTORY_SEPARATOR . basename($safeName . '.token');
    }
}
