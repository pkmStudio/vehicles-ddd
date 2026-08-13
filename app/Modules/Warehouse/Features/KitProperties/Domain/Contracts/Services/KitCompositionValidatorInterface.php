<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\KitProperties\Domain\Exceptions\KitCompositionException;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

interface KitCompositionValidatorInterface
{
    /**
     * Проверяет бизнес-совместимость состава Warehouse-набора перед расчётом свойств.
     * Шаги:
     * 1) Принять коллекцию номенклатур состава с загруженными type/details.
     * 2) Проверить совместимость типов, брендов и специальных правил щёток.
     * 3) Завершить без результата для валидного состава или выбросить доменное исключение.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     *
     * @throws KitCompositionException если состав комплекта нарушает доменные правила
     */
    public function validate(Collection $nomenclatures): void;
}
