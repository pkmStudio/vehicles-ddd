<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Nomenclature;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Доменный факт обновления Warehouse-номенклатуры.
 */
final readonly class NomenclatureUpdated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $nomenclature;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $nomenclature
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $nomenclature)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->nomenclature = is_array($nomenclature)
            ? CatalogEventPayloadDTO::fromArray($nomenclature, 'part_number')
            : $nomenclature;
    }
}
