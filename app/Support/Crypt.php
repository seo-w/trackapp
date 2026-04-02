<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Cifrado simétrico con OpenSSL (AES-256-GCM) usando una clave aplicación (APP_KEY / app.key).
 */
final class Crypt
{
    private const CIPHER = 'aes-256-gcm';

    private const VERSION = "\x01";

    public function __construct(private string $applicationKey)
    {
    }

    /**
     * @param array<string, mixed> $app Típicamente config('app') con índice 'key'
     */
    public static function fromAppConfig(array $app): self
    {
        $key = (string) ($app['key'] ?? '');
        if ($key === '') {
            throw new \RuntimeException('Falta la clave de aplicación (APP_KEY o app.key en config).');
        }

        return new self($key);
    }

    /**
     * Deriva clave binaria de 32 bytes a partir del secreto configurado.
     */
    private function binaryKey(): string
    {
        return hash('sha256', $this->applicationKey, true);
    }

    /**
     * @return non-falsy-string|null Base64 del binario versión|iv|tag|ciphertext
     */
    public function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        $key = $this->binaryKey();
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false || $ivLength < 8) {
            throw new \RuntimeException('Cifrado no disponible en este entorno PHP.');
        }

        $iv = random_bytes($ivLength);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plain,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16,
        );

        if ($ciphertext === false || $tag === '') {
            throw new \RuntimeException('No se pudo cifrar el valor.');
        }

        $payload = self::VERSION . $iv . $tag . $ciphertext;

        return base64_encode($payload);
    }

    /**
     * Recupera texto plano o null si el sobre es inválido.
     */
    public function decrypt(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        $binary = base64_decode($stored, true);
        if ($binary === false) {
            return null;
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            return null;
        }

        $minLen = 1 + $ivLength + 16 + 1;
        if (strlen($binary) < $minLen) {
            return null;
        }

        if ($binary[0] !== self::VERSION) {
            return null;
        }

        $iv = substr($binary, 1, $ivLength);
        $tag = substr($binary, 1 + $ivLength, 16);
        $ciphertext = substr($binary, 1 + $ivLength + 16);

        $key = $this->binaryKey();
        $plain = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain === false ? null : $plain;
    }
}
