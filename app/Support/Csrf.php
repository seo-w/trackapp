<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Token anti-CSRF almacenado en sesión (sincronización por campo oculto o cabecera).
 */
final class Csrf
{
    private Session $session;

    private string $sessionKey;

    private string $fieldName;

    private string $headerName;

    /** @param array<string, mixed> $options Opciones bajo config app.csrf */
    public function __construct(Session $session, array $options = [])
    {
        $this->session = $session;
        $this->sessionKey = (string) ($options['session_key'] ?? '_csrf_secret');
        $this->fieldName = (string) ($options['field_name'] ?? '_csrf_token');
        $this->headerName = (string) ($options['header_name'] ?? 'X-CSRF-TOKEN');
    }

    public function fieldName(): string
    {
        return $this->fieldName;
    }

    public function headerName(): string
    {
        return $this->headerName;
    }

    public function token(): string
    {
        $existing = $this->session->get($this->sessionKey);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $this->session->set($this->sessionKey, $token);

        return $token;
    }

    public function validateToken(?string $token): bool
    {
        $expected = $this->session->get($this->sessionKey);
        if (! is_string($expected) || $expected === '' || ! is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    /**
     * Valida POST: campo del formulario o cabecera HTTP configurada.
     */
    public function validateRequest(): bool
    {
        $field = $this->fieldName;
        $token = null;

        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $token = $_POST[$field];
        }

        if ($token === null) {
            $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $this->headerName));
            if (isset($_SERVER[$serverKey]) && is_string($_SERVER[$serverKey])) {
                $token = $_SERVER[$serverKey];
            }
        }

        return $this->validateToken($token);
    }

    /**
     * HTML seguro para formulario (sin etiquetas form).
     */
    public function fieldHtml(): string
    {
        $name = htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8');
        $value = htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }
}
