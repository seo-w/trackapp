<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Resultado de una operación contra el API Merkaweb (sin excepciones al flujo HTTP).
 */
final readonly class MerkawebResult
{
    /**
     * @param mixed $data
     */
    public function __construct(
        public bool $ok,
        public ?int $httpStatus,
        public string $code,
        public string $message,
        public mixed $data = null,
    ) {
    }

    public static function ok(string $message, ?int $httpStatus, mixed $data): self
    {
        return new self(true, $httpStatus, 'success', $message, $data);
    }

    public static function fail(string $code, string $message, ?int $httpStatus): self
    {
        return new self(false, $httpStatus, $code, $message, null);
    }
}
