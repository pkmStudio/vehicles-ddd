<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services\Strategies;

use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Подбирает упаковку для свечей зажигания по количеству, не по габаритам товара: больше 6 штук —
 * самая большая из существующих коробок, иначе — самая маленькая. Не создаёт новых коробок,
 * поэтому не наследует `AbstractPackagingStrategy`.
 */
final readonly class SparkPlugsPackagingStrategy
{
    private const int MAX_COUNT_PLUGS_FOR_SMALL_BOX = 6;

    /**
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        $volume = fn (PackDimensionData $box): int => $box->length * $box->width * $box->height;

        $largest = $packDimensions->sortByDesc($volume)->first();
        $smallest = $packDimensions->sortBy($volume)->first();

        return count($nomenclatures) > self::MAX_COUNT_PLUGS_FOR_SMALL_BOX ? $largest : $smallest;
    }
}
