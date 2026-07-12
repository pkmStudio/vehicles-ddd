<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Domain\Contracts\Services\Strategies;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Порт стратегии подбора упаковки для конкретного detail-шаблона Warehouse-типа.
 */
interface PackagingStrategyInterface
{
    /**
     * Проверяет, применима ли стратегия к detail-шаблону типа номенклатуры.
     */
    public function supports(?NomenclatureDetailTemplateEnum $template): bool;

    /**
     * Рассчитывает или выбирает упаковку для набора номенклатур.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData;
}
