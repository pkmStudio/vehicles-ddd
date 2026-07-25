<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Services;

use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use Illuminate\Support\Collection;

interface KitCompositionValidatorInterface
{
    /**
     * Проверяет бизнес-совместимость состава Warehouse-набора перед расчётом свойств.
     *
     * @param  Collection<int, NomenclatureData>  $nomenclatures
     */
    public function validate(Collection $nomenclatures): void;
}
