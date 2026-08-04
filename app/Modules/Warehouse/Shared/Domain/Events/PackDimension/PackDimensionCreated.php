<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\PackDimension;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Доменный факт создания упаковочного размера Warehouse.
 */
final readonly class PackDimensionCreated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $packDimension;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $packDimension
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $packDimension)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->packDimension = is_array($packDimension)
            ? CatalogEventPayloadDTO::fromArray($packDimension, 'name')
            : $packDimension;
    }
}
