<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Domain\ModelData;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Минимальный снимок Warehouse-номенклатуры, нужный стратегиям подбора упаковки: артикул (для
 * хардкод-исключений по конкретным товарам), количество в упаковке (для колодок, кладущихся в ряд)
 * и сохранённые характеристики товара (`details`, см. App\Modules\Templates — оттуда стратегии берут
 * `metrics`/`length_main`/`length_second`).
 */
#[MapName(SnakeCaseMapper::class)]
final class NomenclatureData extends Data
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $partNumber,
        public readonly int $quantityInPak,
        public readonly array $details,
        public readonly ?int $id = null,
    ) {}
}
