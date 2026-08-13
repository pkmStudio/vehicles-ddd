<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\Modification;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Фиксирует доменный факт изменения модификаций.
 */
final readonly class ModificationUpdated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $modification;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $modification
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $modification)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->modification = is_array($modification)
            ? CatalogEventPayloadDTO::fromArray($modification, 'mod_id')
            : $modification;
    }
}
