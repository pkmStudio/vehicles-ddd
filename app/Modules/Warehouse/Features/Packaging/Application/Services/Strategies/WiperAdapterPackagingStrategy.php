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
 * Адаптеры щёток стеклоочистителя всегда уходят в одну универсальную маленькую коробку — без
 * расчёта габаритов. Не создаёт новых коробок, поэтому не наследует `AbstractPackagingStrategy`.
 */
final readonly class WiperAdapterPackagingStrategy implements PackagingStrategyInterface
{
    /**
     * Проверяет, что стратегия применима к detail-шаблону адаптеров щёток.
     * Шаги:
     * 1) Принять resolved detail template warehouse type.
     * 2) Сравнить template с WIPER_ADAPTER.
     */
    public function supports(?NomenclatureDetailTemplateEnum $template): bool
    {
        return $template === NomenclatureDetailTemplateEnum::WIPER_ADAPTER;
    }

    /**
     * Этот метод возвращает первую доступную коробку адаптеров.
     * Шаги:
     * 1) Принять тип, состав и список существующих упаковок.
     * 2) Не выполнять расчёт габаритов, потому что адаптеры используют универсальную коробку.
     * 3) Вернуть первую доступную упаковку для типа адаптеров.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     * @param  Collection<int, PackDimensionData>  $packDimensions
     */
    public function calculate(TypeData $type, array $nomenclatures, Collection $packDimensions): PackDimensionData
    {
        return $packDimensions->first();
    }
}
