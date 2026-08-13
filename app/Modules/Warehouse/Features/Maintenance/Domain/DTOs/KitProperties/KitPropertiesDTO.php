<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Domain\DTOs\KitProperties;

final readonly class KitPropertiesDTO
{
    /**
     * Переносит рассчитанные свойства набора из KitProperties boundary в Maintenance-сценарий.
     */
    public function __construct(
        public int $typeId,
        public ?int $packDimensionId,
        public float $weight,
        public int $quantityInPackage,
        public int $quantityPackage,
        public string $complectation,
        public string $importHash,
    ) {}
}
