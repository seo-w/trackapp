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

        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
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
        ]);

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
