<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-номенклатуры для точечных Catalog-мутаций.
 */
#[MapName(SnakeCaseMapper::class)]
final class NomenclatureData extends Data
{
    /**
     * Хранит поля номенклатуры и опционально загруженные справочники.
     *
     * @param  array<int, string>  $material
     * @param  array<int, string>  $vehicleType
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $typeId,
        public readonly int $brandId,
        public readonly string $name,
        public readonly string $country,
        public readonly string $partNumber,
        public readonly string $color,
        public readonly int $weight,
        public readonly array $material,
        public readonly array $vehicleType,
        public readonly int $quantityPak,
        public readonly int $quantityInPak,
        public readonly array $details,
        public readonly ?int $id = null,
        public readonly ?TypeData $type = null,
        public readonly ?BrandData $brand = null,
    ) {}
}
