<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Events;

final readonly class KitEventPayloadDTO
{
    /**
     * Стабильный shared payload набора Warehouse для catalog facts.
     */
    public function __construct(
        public int $id,
        public string $complectation,
        public int $guarantee,
        public int $quantityInPackage,
        public int $quantityPackage,
        public bool $complement,
        public int $weight,
        public int $packDimensionId,
        public int $typeId,
        public ?string $importHash = null,
        public bool $isSaleSeparately = false,
        public bool $isActive = true,
    ) {}
}
