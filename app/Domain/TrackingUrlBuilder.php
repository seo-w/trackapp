<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Construye URL de seguimiento: 17TRACK para carriers conocidos o link_guia del API.
 */
final class TrackingUrlBuilder
{
    private const FC_ENVIA = '100993';

    private const FC_INTERRAPIDISIMO = '100491';

    private const SEVENTEEN_BASE = 'https://www.17track.net/es/track';

    /**
     * @return array{trackingUrl: ?string, originalTrackingUrl: ?string}
     */
    public function build(?string $carrierName, ?string $guideNumber, ?string $linkGuia): array
    {
        $original = $this->sanitizeUrl($linkGuia);
        $guide = $guideNumber !== null ? trim($guideNumber) : '';
        $bucket = $this->carrierBucket($carrierName);

        $trackingUrl = null;

        if ($guide !== '' && $bucket === 'interrapidisimo') {
            $trackingUrl = $this->seventeenTrackUrl($guide, self::FC_INTERRAPIDISIMO);
        } elseif ($guide !== '' && $bucket === 'envia') {
            $trackingUrl = $this->seventeenTrackUrl($guide, self::FC_ENVIA);
        }

        if ($trackingUrl === null && $original !== null) {
            $trackingUrl = $original;
        }

        return [
            'trackingUrl' => $trackingUrl,
            'originalTrackingUrl' => $original,
        ];
    }

    private function seventeenTrackUrl(string $guideNumber, string $fc): string
    {
        $params = http_build_query(
            [
                'nums' => $guideNumber,
                'fc' => $fc,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986,
        );

        return self::SEVENTEEN_BASE . '?' . $params;
    }

    private function sanitizeUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $t = trim($url);
        if ($t === '') {
            return null;
        }

        if (filter_var($t, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $t;
    }

    /**
     * @return 'envia'|'interrapidisimo'|'other'
     */
    private function carrierBucket(?string $carrierName): string
    {
        if ($carrierName === null || trim($carrierName) === '') {
            return 'other';
        }

        $collapsed = str_replace(' ', '', $this->upper(trim($carrierName)));
        if (str_contains($collapsed, 'INTERRAPIDISIMO')) {
            return 'interrapidisimo';
        }
        if (str_contains($collapsed, 'ENVIA')) {
            return 'envia';
        }

        return 'other';
    }

    private function upper(string $value): string
    {
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($value, 'UTF-8');
        }

        return strtoupper($value);
    }
}
