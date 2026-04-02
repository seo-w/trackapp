<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Mapea códigos de estado del API (2, 3, 4) a etiquetas de negocio.
 */
final class StatusMapper
{
    public const DESPACHADO = 2;

    public const ENTREGADO = 3;

    public const DEVUELTO = 4;

    public const LEGALIZADO = 5;

    /** @var array<int, string> */
    private const LABELS = [
        self::DESPACHADO => 'Despachado',
        self::ENTREGADO => 'Entregado',
        self::DEVUELTO => 'Devuelto',
        self::LEGALIZADO => 'Legalizado',
    ];

    public function label(int $statusCode): string
    {
        return self::LABELS[$statusCode] ?? 'Estado desconocido';
    }

    public function isKnown(int $statusCode): bool
    {
        return array_key_exists($statusCode, self::LABELS);
    }

    /** @return list<int> */
    public function knownCodes(): array
    {
        return array_keys(self::LABELS);
    }
}
