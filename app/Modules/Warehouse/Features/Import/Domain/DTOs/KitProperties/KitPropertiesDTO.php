<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs\KitProperties;

final readonly class KitPropertiesDTO
{
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
