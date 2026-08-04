<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle;

/**
 * Передает контекст применения правил записи автомобиля в catalog mutations.
 */
final readonly class VehicleMutationWriteContextDTO
{
    /**
     * Инициализирует immutable-контекст для логов и диагностики masked fields.
     */
    public function __construct(
        public ?string $operationId = null,
        public ?int $ownerExternalId = null,
    ) {}
}
