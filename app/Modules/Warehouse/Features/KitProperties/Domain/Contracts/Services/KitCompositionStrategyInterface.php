<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;
use Illuminate\Support\Collection;

/**
 * Стратегия определения состава/типа набора. В отличие от Packaging-стратегий (выбираются `match`
 * по шаблону), стратегии состава перебираются по порядку (chain of responsibility) —
 * `KitPropertiesService` держит их как упорядоченный массив и берёт первую подходящую, поэтому
 * здесь нужен настоящий полиморфный интерфейс, не просто "простой класс без порта".
 */
interface KitCompositionStrategyInterface
{
    /**
     * Подходит ли эта стратегия для данного набора номенклатур?
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    public function supports(Collection $nomenclatures): bool;

    /**
     * Возвращает итоговый тип набора.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    public function resolveType(Collection $nomenclatures): TypeData;

    /**
     * Основные номенклатуры — те, что участвуют в расчёте упаковки, количества и комплектации.
     * Вспомогательные (адаптеры и т.п.) исключаются.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     * @return Collection<int, NomenclatureData>
     */
    public function primaryNomenclatures(Collection $nomenclatures): Collection;
}
