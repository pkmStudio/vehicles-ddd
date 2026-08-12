<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\KitPropertiesDTO;
use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;

/**
 * Порт расчёта производных свойств Warehouse-набора (Kit) по его составу.
 */
interface KitPropertiesServiceInterface
{
    /**
     * Считает комплектацию/вес/quantity/упаковку для набора из переданных номенклатур.
     * Шаги:
     * 1) Проверить состав набора через валидатор.
     * 2) Выбрать стратегию состава и определить итоговый type.
     * 3) Рассчитать упаковку, количества, вес, комплектацию и hash состава.
     * 4) Вернуть DTO производных свойств для записи в Kit.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures  непустой список, каждая — с загруженным `type`
     *
     * @throws KitCompositionException если состав набора некорректен
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
