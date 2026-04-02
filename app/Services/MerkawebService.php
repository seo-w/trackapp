<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AppSettingsRepository;
use App\Support\Crypt;
use App\Support\CurlHttpClient;

/**
 * Integración server-side con el API Merkaweb (órdenes por estado).
 */
final class MerkawebService
{
    /** @var list<int> */
    public const ALLOWED_ESTADOS = [2, 3, 4, 5];

    /** @var AppSettingsRepository */
    private $settingsRepository;

    /** @var Crypt */
    private $crypt;

    /** @var CurlHttpClient */
    private $http;

    public function __construct(
        $settingsRepository,
        $crypt,
        $http
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->crypt = $crypt;
        $this->http = $http;
    }

    /**
     * @param array<string, mixed> $appConfig Típicamente config('app') completo (key + merkaweb)
     * @param CurlHttpClient|null $http Inyectable en pruebas
     */
    public static function fromApp(array $appConfig, AppSettingsRepository $repository, $http = null)
    {
        $mw = $appConfig['merkaweb'] ?? [];
        if (! is_array($mw)) {
            $mw = [];
        }

        $client = $http ?? new CurlHttpClient(
            (float) ($mw['http_timeout'] ?? 15),
            (float) ($mw['http_connect_timeout'] ?? 5),
            (bool) ($mw['ssl_verify'] ?? false)
        );

        return new self(
            $repository,
            Crypt::fromAppConfig($appConfig),
            $client
        );
    }

    /**
     * Prueba conectividad y credenciales con una consulta GET a órdenes en estado 2.
     */
    public function testConnection()
    {
        $result = $this->findOrdenesByEstado(2);
        if (! $result->ok) {
            return $result;
        }

        return MerkawebResult::ok(
            'La conexión con Merkaweb responde correctamente (prueba con estado 2).',
            $result->httpStatus,
            $result->data,
        );
    }

    /**
     * GET /ordenes/find?tienda_id=…&estado=… con Authorization: Bearer …
     */
    /**
     * GET /ordenes/find?tienda_id=…&estado=… con Authorization: Bearer …
     */
    /**
     * GET /ordenes/find?tienda_id=…&estado=… con Authorization: Bearer …
     */
    public function findOrdenesByEstado($estado, $fechaDesde = null, $fechaHasta = null)
    {
        if (! in_array($estado, self::ALLOWED_ESTADOS, true)) {
            return MerkawebResult::fail(
                'invalid_estado',
                'Solo se permiten los estados 2, 3, 4 y 5.',
                null,
            );
        }

        $credentials = $this->resolveCredentials();
        if ($credentials instanceof MerkawebResult) {
            return $credentials;
        }

        $url = $this->buildFindUrl($credentials['api_base_url'], $credentials['tienda_id'], $estado, $fechaDesde, $fechaHasta);
        $response = $this->http->get($url, [
            'Authorization' => 'Bearer ' . $credentials['token'],
            'Accept' => 'application/json',
        ]);

        return $this->interpretHttpResponse($response);
    }

    /**
     * GET /productos/{id} con Authorization: Bearer …
     */
    public function findProductoById($productId)
    {

        $credentials = $this->resolveCredentials();
        if ($credentials instanceof MerkawebResult) {
            return $credentials;
        }

        $url = $credentials['api_base_url'] . '/productos/' . $productId;
        $response = $this->http->get($url, [
            'Authorization' => 'Bearer ' . $credentials['token'],
            'Accept' => 'application/json',
        ]);

        return $this->interpretHttpResponse($response);
    }


    /**
     * @return array{api_base_url: string, tienda_id: string, token: string}|MerkawebResult
     */
    private function resolveCredentials()
    {
        $row = $this->settingsRepository->first();
        if ($row === null) {
            return MerkawebResult::fail(
                'config',
                'No hay configuración guardada. Completa y guarda el formulario antes de consultar el API.',
                null,
            );
        }

        $base = trim((string) ($row['api_base_url'] ?? ''));
        if ($base === '') {
            return MerkawebResult::fail(
                'config',
                'Falta la URL base del API en la configuración.',
                null,
            );
        }

        $tienda = trim((string) ($row['tienda_id'] ?? ''));
        if ($tienda === '') {
            return MerkawebResult::fail(
                'config',
                'Falta el identificador de tienda en la configuración.',
                null,
            );
        }

        $encrypted = $row['access_token_encrypted'] ?? null;
        $plain = $this->crypt->decrypt(is_string($encrypted) ? $encrypted : null);

        if ($plain === null || $plain === '') {
            if ($encrypted === null || $encrypted === '') {
                // Token por defecto solicitado
                $plain = '24120|ZbJy55FYtX4tTotvdJswcVZWlD0Y5e4moqTC2pyr';
            } else {
                return MerkawebResult::fail(
                    'config',
                    'No se pudo descifrar el token. Revisa APP_KEY y vuelve a guardar el token.',
                    null,
                );
            }
        }

        return [
            'api_base_url' => rtrim($base, '/'),
            'tienda_id' => $tienda,
            'token' => $plain,
        ];
    }

    private function buildFindUrl($apiBaseUrl, $tiendaId, $estado, $fechaDesde = null, $fechaHasta = null)
    {
        $params = [
            'tienda_id' => $tiendaId,
            'estado' => $estado,
        ];
        
        if (is_string($fechaDesde) && $fechaDesde !== '') {
            $params['fecha_desde'] = $fechaDesde;
        }
        
        if (is_string($fechaHasta) && $fechaHasta !== '') {
            $params['fecha_hasta'] = $fechaHasta;
        }

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return $apiBaseUrl . '/ordenes/find?' . $query;
    }

    /**
     * @param array{error: ?string, status: int, body: string, curl_errno: int} $response
     */
    private function interpretHttpResponse($response)
    {
        $status = $response['status'];
        $body = $response['body'];
        $curlErr = $response['error'];
        $errno = $response['curl_errno'];

        if ($curlErr !== null && $curlErr !== '') {
            return MerkawebResult::fail(
                'curl',
                $this->curlFailureMessage($errno, $curlErr),
                $status !== 0 ? $status : null,
            );
        }

        if ($status === 401 || $status === 403) {
            return MerkawebResult::fail(
                'auth',
                'El API rechazó la autenticación (HTTP ' . $status . '). Revisa el token de acceso.',
                $status,
            );
        }

        if ($status < 200 || $status >= 300) {
            $snippet = trim(substr($body, 0, 280));
            $detail = $snippet !== '' ? ' Respuesta: ' . $snippet : '';

            return MerkawebResult::fail(
                'http',
                'Respuesta HTTP inesperada (' . $status . ').' . $detail,
                $status,
            );
        }

        $trimmed = trim($body);
        if ($trimmed === '') {
            return MerkawebResult::ok('Respuesta vacía aceptada.', $status, null);
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return MerkawebResult::fail(
                'invalid_body',
                'El API devolvió un cuerpo que no es JSON válido.',
                $status,
            );
        }

        return MerkawebResult::ok('OK', $status, $decoded);
    }

    private function curlFailureMessage($errno, $curlError)
    {
        if ($errno === 28) {
            return 'Tiempo de espera agotado al contactar con Merkaweb.';
        }

        if (in_array($errno, [6, 7], true)) {
            return 'No se pudo conectar con el host del API. Comprueba la URL base y la red.';
        }

        return $curlError !== ''
            ? 'Error de red al llamar al API: ' . $curlError
            : 'Error de red al llamar al API (código ' . $errno . ').';
    }
}
