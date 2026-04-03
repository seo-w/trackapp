<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Cliente HTTP mínimo basado en cURL (sin dependencias externas).
 */
final class CurlHttpClient
{
    /** @var float */
    private $timeoutSeconds;

    /** @var float */
    private $connectTimeoutSeconds;

    /** @var bool */
    private $sslVerify;

    public function __construct(
        float $timeoutSeconds = 15.0,
        float $connectTimeoutSeconds = 5.0,
        bool $sslVerify = true
    ) {
        $this->timeoutSeconds = $timeoutSeconds;
        $this->connectTimeoutSeconds = $connectTimeoutSeconds;
        $this->sslVerify = $sslVerify;
    }

    /**
     * @param array<string, string> $headers Cabeceras en formato "Nombre: valor"
     * @return array{error: ?string, status: int, body: string, curl_errno: int}
     */
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }

    /**
     * @param array<string, mixed> $data Datos a enviar en el body (JSON)
     * @param array<string, string> $headers Cabeceras
     * @return array{error: ?string, status: int, body: string, curl_errno: int}
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * @param array<string, mixed>|null $data Datos (para POST/PUT se envían como JSON)
     * @param array<string, string> $headers Cabeceras
     */
    private function request(string $method, string $url, ?array $data = null, array $headers = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'error' => 'No se pudo inicializar cURL.',
                'status' => 0,
                'body' => '',
                'curl_errno' => 0,
            ];
        }

        $headerList = [];
        foreach ($headers as $name => $value) {
            $headerList[] = $name . ': ' . $value;
        }

        $opts = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => (int) max(1, round($this->timeoutSeconds)),
            CURLOPT_CONNECTTIMEOUT => (int) max(1, round($this->connectTimeoutSeconds)),
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => $headerList,
            CURLOPT_USERAGENT => 'TrackApp/MerkawebService (PHP cURL)',
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_SSL_VERIFYHOST => $this->sslVerify ? 2 : 0,
        ];

        if ($method === 'POST' && $data !== null) {
            $json = json_encode($data);
            $opts[CURLOPT_POSTFIELDS] = $json !== false ? $json : '';
            $headerList[] = 'Content-Type: application/json';
            $headerList[] = 'Content-Length: ' . strlen($opts[CURLOPT_POSTFIELDS]);
            $opts[CURLOPT_HTTPHEADER] = $headerList;
        }

        curl_setopt_array($ch, $opts);

        $body = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            return [
                'error' => $curlError !== '' ? $curlError : 'La petición HTTP falló.',
                'status' => $status,
                'body' => '',
                'curl_errno' => $curlErrno,
            ];
        }

        return [
            'error' => $curlErrno !== 0 ? $curlError : null,
            'status' => $status,
            'body' => $body,
            'curl_errno' => $curlErrno,
        ];
    }
}
