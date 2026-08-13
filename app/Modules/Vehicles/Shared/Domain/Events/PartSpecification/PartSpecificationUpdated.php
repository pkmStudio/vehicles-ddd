<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\Events\PartSpecification;

use App\Modules\Vehicles\Shared\Domain\DTOs\Events\CatalogEventPayloadDTO;

/**
 * Фиксирует доменный факт обновления спецификации детали.
 */
final readonly class PartSpecificationUpdated
{
    public int $userId;

    public string $operationId;

    public CatalogEventPayloadDTO $specification;

    /**
     * @param  array<string, mixed>|CatalogEventPayloadDTO  $specification
     */
    public function __construct(int $userId, string $operationId, array|CatalogEventPayloadDTO $specification)
    {
        $this->userId = $userId;
        $this->operationId = $operationId;
        $this->specification = is_array($specification)
            ? CatalogEventPayloadDTO::fromArray($specification)
            : $specification;
    }
}
