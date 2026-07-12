<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-набора для точечных Catalog-мутаций.
 */
#[MapName(SnakeCaseMapper::class)]
final class KitData extends Data
{
    /**
     * Хранит поля набора, готовые к записи после расчёта KitProperties.
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
    ) {}
}
