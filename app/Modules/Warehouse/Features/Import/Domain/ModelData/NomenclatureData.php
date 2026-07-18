<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-номенклатуры для Import: запись через Command и чтение сохранённых строк для Kit.
 */
#[MapName(SnakeCaseMapper::class)]
final class NomenclatureData extends Data
{
    /**
     * Хранит поля номенклатуры, которые пишет Import, и optional type для read-сценариев Kit.
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
    ) {}
}
