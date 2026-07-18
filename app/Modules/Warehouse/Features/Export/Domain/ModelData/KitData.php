<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\ModelData;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-набора с загруженным составом номенклатуры для Excel-строки.
 */
#[MapName(SnakeCaseMapper::class)]
final class KitData extends Data
{
    /**
     * Хранит поля набора и связи, которые реально читает Export.
     *
     * @param  Collection<int, NomenclatureData>|null  $nomenclatures  заполняется KitRepository::all()
     */
    public function __construct(
        public readonly string $complectation,
        public readonly int $guarantee,
        public readonly int $quantityInPackage,
        public readonly int $quantityPackage,
        public readonly bool $complement,
        public readonly int $weight,
        public readonly int $packDimensionId,
        public readonly int $typeId,
        public readonly ?string $importHash = null,
        public readonly bool $isSaleSeparately = false,
        public readonly bool $isActive = true,
        public readonly ?int $id = null,
        public readonly ?PackDimensionData $packDimension = null,
        public readonly ?TypeData $type = null,
        public readonly ?Collection $nomenclatures = null,
    ) {}
}
