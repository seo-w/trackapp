<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Transforma un pedido en formato crudo del API Merkaweb al modelo interno estable.
 *
 * Claves de entrada admitidas (se usa la primera disponible por grupo):
 * id, id_pedido, pedido_id, order_id, numero_pedido… | estado | nombre/apellido |
 * telefono | transportadora | guía | link_guia | fechas | motivo_devolucion…
 */
final class OrderNormalizer
{
    public function __construct(
        private StatusMapper $statusMapper = new StatusMapper(),
        private TrackingUrlBuilder $trackingUrlBuilder = new TrackingUrlBuilder(),
    ) {
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{
     *   orderId: string,
     *   productId: int,
     *   statusCode: int,
     *   statusLabel: string,
     *   customerName: string,
     *   customerPhone: string,
     *   carrierName: ?string,
     *   guideNumber: ?string,
     *   trackingUrl: ?string,
     *   originalTrackingUrl: ?string,
     *   mainEventDate: ?string,
     *   dispatchDate: ?string,
     *   deliveryDate: ?string,
     *   returnDate: ?string,
     *   returnReason: ?string,
     *   total: float,
     *   costo: float,
     *   costoEnvio: float,
     *   summaryText: string,
     *   city: string,
     *   raw: array<string, mixed>
     * }
     */
    public function normalize(array $raw): array

    {
        $orderId = (string) $this->firstScalar($raw, [
            'id', 'id_pedido', 'pedido_id', 'order_id', 'numero_pedido', 'pedido',
        ]);

        $statusCode = $this->intStatus($this->firstScalar($raw, ['estado', 'estado_id', 'status', 'estado_pedido']));

        $clienteObj = (isset($raw['cliente']) && is_array($raw['cliente'])) ? $raw['cliente'] : [];

        $nombre = $this->stringOrEmpty(
            $this->firstScalar($raw, ['nombre', 'nombres', 'nombre_cliente', 'cliente_nombre']) ?? 
            $this->firstScalar($clienteObj, ['cliente_name', 'nombre'])
        );
        $apellido = $this->stringOrEmpty(
            $this->firstScalar($raw, ['apellido', 'apellidos', 'apellido_cliente', 'cliente_apellido']) ?? 
            $this->firstScalar($clienteObj, ['cliente_last_name', 'apellido'])
        );
        
        $customerName = trim($nombre . ' ' . $apellido);
        if ($customerName === '') {
            $customerName = trim($this->stringOrEmpty(
                $this->firstScalar($raw, ['cliente', 'nombre_completo']) ?? 
                $this->firstScalar($clienteObj, ['nombre_completo'])
            ));
        }

        $phone = $this->stringOrEmpty(
            $this->firstScalar($raw, ['telefono', 'telefono_cliente', 'celular', 'movil', 'phone']) ?? 
            $this->firstScalar($clienteObj, ['celular', 'telefono'])
        );

        $city = $this->stringOrEmpty(
            $this->firstScalar($raw, ['ciudad', 'municipio', 'city', 'poblacion']) ?? 
            $this->firstScalar($clienteObj, ['ciudad', 'municipio', 'city'])
        );

        if ($city === '') {
            $rawCiudad = $raw['Ciudad'] ?? $raw['ciudad'] ?? [];
            $ciudadObj = is_array($rawCiudad) ? $rawCiudad : [];
            
            $cName = $this->stringOrEmpty($this->firstScalar($ciudadObj, ['ciudad_name', 'poblacion_name', 'nombre']));
            $dName = $this->stringOrEmpty($this->firstScalar($ciudadObj, ['departamento_name', 'nombre_departamento', 'departamento']));
            
            if ($cName !== '') {
                $city = $dName !== '' ? sprintf('%s (%s)', $cName, $dName) : $cName;
            }
        }

        if ($city === '') {
            $city = 'No indicada';
        }

        $carrier = $this->nullableString($this->firstScalar($raw, [
            'transportadora', 'transportista', 'carrier', 'courier',
        ]));

        $guide = $this->nullableString($this->firstScalar($raw, [
            'numero_guia', 'guia', 'numeroGuia', 'tracking_number', 'tracking', 'guide_number',
        ]));

        $linkGuia = $this->nullableString($this->firstScalar($raw, [
            'link_guia', 'url_guia', 'linkGuia', 'tracking_url',
        ]));

        $dispatchDate = $this->nullableString($this->firstScalar($raw, ['fecha_despacho', 'fechaDespacho', 'dispatch_date']));
        $deliveryDate = $this->nullableString($this->firstScalar($raw, ['fecha_entrega', 'fechaEntrega', 'delivery_date']));
        $returnDate = $this->nullableString($this->firstScalar($raw, ['fecha_devolucion', 'fechaDevolucion', 'return_date']));
        $returnReason = $this->nullableString($this->firstScalar($raw, [
            'motivo_devolucion', 'motivoDevolucion', 'razon_devolucion', 'return_reason',
        ]));

        $urls = $this->trackingUrlBuilder->build($carrier, $guide, $linkGuia);

        $mainEventDate = $this->resolveMainEventDate($statusCode, $dispatchDate, $deliveryDate, $returnDate);

        $statusLabel = $this->statusMapper->label($statusCode);
        
        $total = $this->floatValue($this->firstScalar($raw, ['total', 'valor_total', 'precio_total']));
        $costo = $this->floatValue($this->firstScalar($raw, ['costo', 'valor_costo', 'precio_costo']));
        $costoEnvio = $this->floatValue($this->firstScalar($raw, ['costo_envio', 'costoEnvio', 'valor_envio', 'flete']));

        $summaryText = $this->buildSummary(
            $orderId,
            $customerName,
            $phone,
            $carrier,
            $guide,
            $statusLabel,
            $mainEventDate,
        );

        $productId = (string) $this->firstScalar($raw, [
            'id_variacion', 'variant_id', 'producto_id', 'product_id', 'id_producto', 'item_id', 'productId', 'skuid',
        ]);

        // Si no se encontró en la raíz, buscar en el primer elemento de 'productos' si existe
        if ($productId === '' && isset($raw['productos']) && is_array($raw['productos']) && !empty($raw['productos'])) {
            $p0 = $raw['productos'][0];
            if (is_array($p0)) {
                $productId = (string) $this->firstScalar($p0, [
                    'id_variacion', 'variant_id', 'producto_id', 'product_id', 'id_producto', 'id', 'item_id',
                ]);
            }
        }




        $cityName = $city;
        $departmentName = '';

        if (str_contains($city, ' (')) {
            $parts = explode(' (', $city);
            $cityName = trim($parts[0]);
            $departmentName = trim(str_replace(')', '', $parts[1]));
        }

        $eventDate = '—';
        $eventTime = '';
        if ($mainEventDate !== null && $mainEventDate !== '') {
            $dateParts = explode(' ', $mainEventDate);
            $eventDate = $dateParts[0];
            $eventTime = $dateParts[1] ?? '';
        }

        // Fallback de producto desde el pedido
        $orderProductName = '';
        $orderProductImage = null;
        if (isset($raw['productos']) && is_array($raw['productos']) && !empty($raw['productos'])) {
            $first = $raw['productos'][0];
            $orderProductName = (string) ($first['producto_name'] ?? $first['nombre_producto'] ?? $first['producto'] ?? $first['nombre'] ?? $first['name'] ?? $first['product_name'] ?? '');
            
            $img = $first['imagen'] ?? $first['image'] ?? $first['url_imagen'] ?? $first['url'] ?? null;
            if (is_array($img) && isset($img['url'])) {
                $img = $img['url'];
            } elseif (is_array($img) && !empty($img)) {
                $img = $img[0]['url'] ?? $img[0] ?? null;
            }
            $orderProductImage = is_string($img) ? $img : null;
        }


        return [
            'orderId' => $orderId,
            'productId' => $productId,
            'statusCode' => $statusCode,
            'statusLabel' => $statusLabel,
            'customerName' => $customerName,
            'customerPhone' => $phone,
            'carrierName' => $carrier,
            'guideNumber' => $guide,
            'trackingUrl' => $urls['trackingUrl'],
            'originalTrackingUrl' => $urls['originalTrackingUrl'],
            'mainEventDate' => $mainEventDate,
            'eventDate' => $eventDate,
            'eventTime' => $eventTime,
            'dispatchDate' => $dispatchDate,
            'deliveryDate' => $deliveryDate,
            'returnDate' => $returnDate,
            'returnReason' => $returnReason,
            'total' => $total,
            'costo' => $costo,
            'costoEnvio' => $costoEnvio,
            'summaryText' => $summaryText,
            'city' => $city,
            'cityName' => $cityName,
            'departmentName' => $departmentName,
            'orderProductName' => $orderProductName,
            'orderProductImage' => $orderProductImage,
            'raw' => $raw,
        ];

    }


    private function resolveMainEventDate(
        int $statusCode,
        ?string $dispatchDate,
        ?string $deliveryDate,
        ?string $returnDate,
    ): ?string {
        switch ($statusCode) {
            case StatusMapper::DESPACHADO:
                return $dispatchDate;
            case StatusMapper::ENTREGADO:
                return $deliveryDate;
            case StatusMapper::DEVUELTO:
                return $returnDate;
            default:
                return $dispatchDate ?? $deliveryDate ?? $returnDate;
        }
    }

    /**
     * Plantilla fija de negocio.
     */
    private function buildSummary(
        string $orderId,
        string $customerName,
        string $phone,
        ?string $carrier,
        ?string $guide,
        string $statusLabel,
        ?string $mainEventDate,
    ): string {
        $nombre = $customerName !== '' ? $customerName : 'cliente sin nombre';
        $telefono = $phone !== '' ? $phone : 'no indicado';
        $transportadora = ($carrier !== null && $carrier !== '') ? $carrier : 'sin transportadora';
        $guia = ($guide !== null && $guide !== '') ? $guide : 'sin guía';

        $base = sprintf(
            'El pedido %s de %s, con número telefónico %s, enviado por %s, con la guía %s, tiene estado %s',
            $orderId,
            $nombre,
            $telefono,
            $transportadora,
            $guia,
            $statusLabel,
        );

        if ($mainEventDate !== null && $mainEventDate !== '') {
            $base .= sprintf(' (%s)', $mainEventDate);
        }

        return $base . '.';
    }

    /**
     * @param list<string> $keys
     */
    private function firstScalar(array $raw, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $raw)) {
                continue;
            }
            $v = $raw[$key];
            if ($v === null) {
                continue;
            }
            if (is_string($v) && trim($v) === '') {
                continue;
            }
            if (is_array($v)) {
                continue;
            }

            return $v;
        }

        return null;
    }

    private function stringOrEmpty(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function nullableString(mixed $value): ?string
    {
        $s = $this->stringOrEmpty($value);

        return $s === '' ? null : $s;
    }

    private function intStatus(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric(trim($value))) {
            return (int) trim($value);
        }
        if (is_float($value)) {
            return (int) $value;
        }

        return 0;
    }

    private function floatValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_float($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return 0.0;
    }
}
