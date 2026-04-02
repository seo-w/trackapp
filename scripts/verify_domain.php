#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Verificación manual de la capa de dominio (StatusMapper, TrackingUrlBuilder, OrderNormalizer).
 * No requiere PHPUnit ni base de datos.
 *
 * Uso: php scripts/verify_domain.php
 */

$basePath = dirname(__DIR__);
define('BASE_PATH', $basePath);

require $basePath . '/app/bootstrap.php';

use App\Domain\OrderNormalizer;
use App\Domain\StatusMapper;
use App\Domain\TrackingUrlBuilder;

/**
 * @param mixed $actual
 */
function expect_same(mixed $actual, mixed $expected, string $label): void
{
    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL [{$label}]: esperado " . json_encode($expected) . ", obtuvo " . json_encode($actual) . PHP_EOL);
        exit(1);
    }
}

function expect_contains(string $haystack, string $needle, string $label): void
{
    if (! str_contains($haystack, $needle)) {
        fwrite(STDERR, "FAIL [{$label}]: no contiene \"{$needle}\"\nTexto: {$haystack}\n");
        exit(1);
    }
}

function pass(string $label): void
{
    fwrite(STDOUT, "OK — {$label}\n");
}

// -- StatusMapper
$map = new StatusMapper();
expect_same($map->label(2), 'Despachado', 'estado 2');
expect_same($map->label(3), 'Entregado', 'estado 3');
expect_same($map->label(4), 'Devuelto', 'estado 4');
expect_same($map->isKnown(2), true, 'isKnown 2');
expect_same($map->isKnown(99), false, 'isKnown 99');
pass('StatusMapper');

// -- TrackingUrlBuilder
$tb = new TrackingUrlBuilder();

$envia = $tb->build('ENVÍA EXPRESS', 'GUIA-001', 'https://carrier.example/guia/1');
expect_contains($envia['trackingUrl'] ?? '', '17track.net', 'ENVIA host');
expect_contains($envia['trackingUrl'] ?? '', 'fc=100993', 'ENVIA fc');
expect_contains($envia['trackingUrl'] ?? '', 'nums=GUIA-001', 'ENVIA nums');
expect_same($envia['originalTrackingUrl'], 'https://carrier.example/guia/1', 'ENVIA original');

$inter = $tb->build('Inter Rapidísimo', 'INT-555', null);
expect_contains($inter['trackingUrl'] ?? '', 'fc=100491', 'INTER fc');
expect_same($inter['originalTrackingUrl'], null, 'INTER sin link');

$otra = $tb->build('OtroCarrier', null, 'https://tracking.otro.com/x');
expect_same($otra['trackingUrl'], 'https://tracking.otro.com/x', 'otra link_guia');
expect_same($otra['originalTrackingUrl'], 'https://tracking.otro.com/x', 'otra original');

$nulls = $tb->build('Desconocido', null, null);
expect_same($nulls['trackingUrl'], null, 'sin URLs');
expect_same($nulls['originalTrackingUrl'], null, 'sin original');

pass('TrackingUrlBuilder');

// -- OrderNormalizer
$norm = new OrderNormalizer();

$raw2 = [
    'id' => 'PED-100',
    'estado' => 2,
    'nombre' => 'Luis',
    'apellido' => 'Martínez',
    'telefono' => '3115550000',
    'transportadora' => 'ENVIA',
    'numero_guia' => 'GW-200',
    'link_guia' => 'https://envia.test/seguimiento',
    'fecha_despacho' => '2026-03-20T10:00:00',
    'fecha_entrega' => null,
    'fecha_devolucion' => null,
];
$o2 = $norm->normalize($raw2);
expect_same($o2['orderId'], 'PED-100', 'orderId');
expect_same($o2['statusCode'], 2, 'code 2');
expect_same($o2['statusLabel'], 'Despachado', 'label 2');
expect_same($o2['customerName'], 'Luis Martínez', 'nombre completo');
expect_same($o2['mainEventDate'], '2026-03-20T10:00:00', 'main fecha despacho');
expect_contains($o2['summaryText'], 'Despachado', 'summary estado 2');
expect_contains($o2['summaryText'], 'PED-100', 'summary id');
expect_contains($o2['summaryText'], '3115550000', 'summary tel');
expect_contains($o2['trackingUrl'] ?? '', '100993', 'normalized tracking ENVIA');
expect_same($o2['raw'], $raw2, 'raw preserved');

$raw3 = [
    'pedido_id' => 42,
    'estado' => '3',
    'nombre' => 'Ana',
    'apellido' => '',
    'telefono' => '',
    'transportadora' => 'X',
    'guia' => 'Z-1',
    'fecha_entrega' => '2026-04-01',
];
$o3 = $norm->normalize($raw3);
expect_same($o3['statusLabel'], 'Entregado', 'label 3');
expect_same($o3['mainEventDate'], '2026-04-01', 'main entrega');

$raw4 = [
    'order_id' => 'R-9',
    'estado' => 4,
    'nombre_completo' => 'Pedido devuelto cliente',
    'telefono' => '999',
    'fecha_devolucion' => '2026-05-01',
    'motivo_devolucion' => 'Rechazado en puerta',
];
$o4 = $norm->normalize($raw4);
expect_same($o4['statusLabel'], 'Devuelto', 'label 4');
expect_same($o4['mainEventDate'], '2026-05-01', 'main devolución');
expect_same($o4['returnReason'], 'Rechazado en puerta', 'motivo');

pass('OrderNormalizer');

fwrite(STDOUT, PHP_EOL . 'Todas las verificaciones pasaron.' . PHP_EOL);
