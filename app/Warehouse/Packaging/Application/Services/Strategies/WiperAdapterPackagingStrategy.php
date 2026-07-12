<?php

declare(strict_types=1);

namespace App\Warehouse\Packaging\Application\Services\Strategies;

use App\Templates\Domain\Enums\NomenclatureDetailTemplateEnum;
use App\Warehouse\Packaging\Domain\Contracts\Services\Strategies\PackagingStrategyInterface;
use App\Warehouse\Packaging\Domain\ModelData\NomenclatureData;
use App\Warehouse\Packaging\Domain\ModelData\PackDimensionData;
use App\Warehouse\Packaging\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Адаптеры щёток стеклоочистителя всегда уходят в одну универсальную маленькую коробку — без
 * расчёта габаритов. Не создаёт новых коробок, поэтому не наследует `AbstractPackagingStrategy`.
 */
final readonly class WiperAdapterPackagingStrategy implements PackagingStrategyInterface
{
    /**
     * Проверяет, что стратегия применима к detail-шаблону адаптеров щёток.
     */
    public function supports(?NomenclatureDetailTemplateEnum $template): bool
    {
        return $template === NomenclatureDetailTemplateEnum::WIPER_ADAPTER;
    }

    /**
     * Этот метод возвращает первую доступную коробку адаптеров.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        return $packDimensions->first();
    }
}
