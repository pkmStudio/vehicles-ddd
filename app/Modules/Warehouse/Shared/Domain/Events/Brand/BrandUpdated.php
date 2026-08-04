<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Brand;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Доменный факт обновления Warehouse-бренда.
 */
final readonly class BrandUpdated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $brand;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $brand
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $brand)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->brand = is_array($brand)
            ? CatalogEventPayloadDTO::fromArray($brand, 'name')
            : $brand;
    }
}
