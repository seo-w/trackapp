<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Normaliza distintas formas de lista de órdenes en la respuesta JSON del API.
 */
final class MerkawebOrderPayload
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function extractOrderRows(mixed $decoded): array
    {
        if ($decoded === null) {
            return [];
        }

        if (is_array($decoded) && self::isList($decoded)) {
            $rows = [];
            foreach ($decoded as $item) {
                if (is_array($item)) {
                    $rows[] = $item;
                }
            }

            return $rows;
        }

        if (is_array($decoded)) {
            foreach (['ordenes', 'data', 'items', 'results', 'rows', 'pedidos'] as $key) {
                if (isset($decoded[$key])) {
                    return self::extractOrderRows($decoded[$key]);
                }
            }

            if (self::looksLikeOrderAssoc($decoded)) {
                return [$decoded];
            }
        }

        return [];
    }

    /**
     * @param array<mixed> $arr
     */
    private static function isList(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function looksLikeOrderAssoc(array $row): bool
    {
        foreach (['id', 'pedido_id', 'order_id', 'numero_pedido', 'id_pedido'] as $k) {
            if (array_key_exists($k, $row)) {
                return true;
            }
        }

        return false;
    }
}
