<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\DTOs\Events;

final readonly class NomenclatureEventPayloadDTO
{
    /**
     * @param  array<int, string>  $material
     * @param  array<int, string>  $vehicleType
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public int $id,
        public int $typeId,
        public int $brandId,
        public string $name,
        public string $country,
        public string $partNumber,
        public string $color,
        public int $weight,
        public array $material,
        public array $vehicleType,
        public int $quantityPak,
        public int $quantityInPak,
        public array $details,
    ) {}
}
