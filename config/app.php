<?php

declare(strict_types=1);

return [
    'name' => 'TrackApp',
    'tagline' => 'Consulta de estado de pedidos y seguimiento',
    'locale' => 'es',
    'charset' => 'UTF-8',

    /**
     * Secreto para cifrado de tokens (APP_KEY en .env recomendado).
     * Generar ejemplo: openssl rand -hex 32
     */
    'key' => (string) (getenv('APP_KEY') ?: ''),

    /**
     * URL pública base (sin barra final). Vacío = rutas relativas a la raíz del host.
     */
    'url' => rtrim((string) (getenv('APP_URL') ?: ''), '/'),

    'session' => [
        'name' => 'TRACKAPP_SESSION',
        /** Tiempo de vida del ID de sesión en segundos (0 = hasta cerrar navegador según cookie). */
        'cookie_lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 0),
        'path' => '/',
        'domain' => (string) (getenv('SESSION_DOMAIN') ?: ''),
        'secure' => filter_var(getenv('SESSION_SECURE') ?: false, FILTER_VALIDATE_BOOLEAN),
        'httponly' => true,
        /** Lax | Strict | None */
        'samesite' => 'Lax',
    ],

    'csrf' => [
        /** Clave interna en $_SESSION donde se guarda el token actual. */
        'session_key' => '_csrf_secret',
        /** Nombre del campo en formularios HTML. */
        'field_name' => '_csrf_token',
        /** Cabecera opcional para peticiones fetch / XMLHttpRequest. */
        'header_name' => 'X-CSRF-TOKEN',
    ],

    /** Cliente HTTP hacia Merkaweb (cURL). */
    'merkaweb' => [
        'http_timeout' => (float) (getenv('MERKAWEB_HTTP_TIMEOUT') ?: 15),
        'http_connect_timeout' => (float) (getenv('MERKAWEB_HTTP_CONNECT_TIMEOUT') ?: 5),
    ],
];
