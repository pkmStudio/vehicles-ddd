<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\DTOs\Nomenclature;

/**
 * DTO входящей команды на обновление Warehouse-номенклатуры.
 */
final readonly class UpdateNomenclatureRequestDTO
{
    /**
     * @param  array<int, string>  $material
     * @param  array<int, string>  $vehicleType
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public int $userId,
        public string $operationId,
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
