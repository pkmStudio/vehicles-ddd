<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Packaging\Application\Services\Strategies;

use App\Modules\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Modules\Warehouse\Features\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Подбирает упаковку для свечей зажигания по количеству, не по габаритам товара: больше 6 штук —
 * самая большая из существующих коробок, иначе — самая маленькая. Не создаёт новых коробок,
 * поэтому не наследует `AbstractPackagingStrategy`.
 */
final readonly class SparkPlugsPackagingStrategy implements PackagingStrategyInterface
{
    private const int MAX_COUNT_PLUGS_FOR_SMALL_BOX = 6;

    /**
     * Проверяет, что стратегия применима к detail-шаблону свечей зажигания.
     */
    public function supports(?NomenclatureDetailTemplateEnum $template): bool
    {
        return $template === NomenclatureDetailTemplateEnum::SPARK_PLUGS;
    }

    /**
     * Этот метод выбирает существующую коробку для свечей по количеству номенклатур.
     *
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
