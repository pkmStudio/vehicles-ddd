<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Валидированный снимок Warehouse-набора (Kit), готовый к записи Command'ом. Производные свойства
 * (комплектация/вес/quantity/тип/упаковка) уже посчитаны `KitPropertiesService` — сюда приходит
 * итог, сама фича Import расчётом не занимается.
 */
#[MapName(SnakeCaseMapper::class)]
final class KitData extends Data
{
    /**
     * Хранит поля набора, которые пишет Import Command.
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
