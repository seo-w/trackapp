<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;

/**
 * Servicio para gestionar la información de productos con caché local.
 */
final class ProductService
{
    public function __construct(
        private MerkawebService $merkaweb,
        private ProductRepository $repository,
    ) {
    }

    /**
     * Busca un producto por ID. Intenta en caché local primero.
     * Si no existe o tiene más de 24 horas, consulta el API.
     *
     * @return array{id: string, name: string, warehouse: string, description: ?string, image_url: ?string}
     */
    public function getById(string $id): array
    {
        if ($id === '' || $id === '0') {
             return $this->placeholder('0');
        }

        $cached = $this->repository->findByRemoteId($id);

        if ($cached !== null) {
            $updatedAt = strtotime((string)($cached['updated_at'] ?? '0'));
            // Cache por 24 horas
            if (time() - $updatedAt < 86400) {
                return [
                    'id'          => (string) $cached['remote_id'],
                    'name'        => (string) ($cached['name'] ?? 'Producto ' . $id),
                    'warehouse'   => (string) ($cached['warehouse'] ?? 'No indicada'),
                    'description' => $cached['description'],
                    'image_url'   => $cached['image_url'],
                ];
            }
        }

        // Si no hay caché o expiró, llamamos al API
        $result = $this->merkaweb->findProductoById($id);
        
        if (! $result->ok || ! is_array($result->data)) {

            if ($cached !== null) {
                return [
                    'id'          => (string) $cached['remote_id'],
                    'name'        => (string) ($cached['name'] ?? 'Producto ' . $id),
                    'warehouse'   => (string) ($cached['warehouse'] ?? 'No indicada'),
                    'description' => $cached['description'],
                    'image_url'   => $cached['image_url'],
                ];
            }
            return $this->placeholder($id);
        }

        $data = $result->data;
        $raw = $data['data'] ?? $data;
        if (! is_array($raw)) {
            return $this->placeholder($id);
        }
        
        // Mapeo según estructura del usuario
        $pName = (string) ($raw['producto_name'] ?? $raw['nombre'] ?? $raw['name'] ?? 'Producto ' . $id);
        $pWarehouse = 'No indicada';
        if (isset($raw['bodega']) && is_array($raw['bodega'])) {
            $pWarehouse = (string) ($raw['bodega']['bodega_name'] ?? 'No indicada');
        }

        $productData = [
            'remote_id'   => $id,
            'name'        => $pName,
            'description' => (string) ($raw['descripcion'] ?? $raw['description'] ?? ''),
            'warehouse'   => $pWarehouse,
            'image_url'   => $this->extractImage($raw),
            'raw_json'    => json_encode($data),
        ];

        $this->repository->save($productData);

        return [
            'id'          => $productData['remote_id'],
            'name'        => $productData['name'],
            'warehouse'   => $productData['warehouse'],
            'description' => $productData['description'],
            'image_url'   => $productData['image_url'],
        ];
    }

    /**
     * @param list<string> $ids
     * @return array<string, array{id: string, name: string, warehouse: string, description: ?string, image_url: ?string}>
     */
    public function getMultiple(array $ids): array
    {
        $uniqueIds = array_unique(array_filter($ids));
        $out = [];
        foreach ($uniqueIds as $id) {
            $out[(string)$id] = $this->getById((string)$id);
        }
        return $out;
    }

    private function extractImage(array $raw): ?string
    {
        $imgs = $raw['images'] ?? $raw['imagenes'] ?? [];
        if (is_array($imgs) && count($imgs) > 0) {
            $first = $imgs[0];
            if (is_array($first) && isset($first['url'])) {
                return (string) $first['url'];
            }
            if (is_string($first)) {
                return $first;
            }
        }
        return null;
    }

    /** @return array{id: string, name: string, warehouse: string, description: ?string, image_url: ?string} */
    private function placeholder(string $id): array
    {
        return [
            'id'          => $id,
            'name'        => 'Producto ID ' . $id,
            'warehouse'   => 'Cargando...',
            'description' => null,
            'image_url'   => null,
        ];
    }
}

