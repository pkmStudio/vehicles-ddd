<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Events\Kit;

use App\Modules\Warehouse\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Доменный факт обновления Warehouse-набора.
 */
final readonly class KitUpdated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $kit;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $kit
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $kit)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->kit = is_array($kit)
            ? CatalogEventPayloadDTO::fromArray($kit, 'import_hash')
            : $kit;
    }
}
