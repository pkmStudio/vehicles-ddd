<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Снимок Warehouse-номенклатуры, нужный для расчёта свойств набора: количество/вес — для
 * quantity/weight, материал — для текста комплектации, `type` — для выбора стратегии состава и
 * резолва детального шаблона. Вызывающий (будущий `KitImport`) обязан загрузить `type` заранее —
 * сама фича к БД не обращается вообще (см. §8 плана).
 */
#[MapName(SnakeCaseMapper::class)]
final class NomenclatureData extends Data
{
    /**
     * @param  array<int, string>  $material
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly int $typeId,
        public readonly string $partNumber,
        public readonly int $quantityInPak,
        public readonly int $quantityPak,
        public readonly int $weight,
        public readonly array $material,
        public readonly array $details,
        public readonly ?int $id = null,
        public readonly ?TypeData $type = null,
    ) {}
}
