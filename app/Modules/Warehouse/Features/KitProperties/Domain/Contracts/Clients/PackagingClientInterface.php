<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\Packaging\PackDimensionDTO;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;

interface PackagingClientInterface
{
    /**
     * Подбирает или создаёт упаковочный размер для состава набора через Packaging boundary.
     * Шаги:
     * 1) Принять тип набора и primary-номенклатуры в языке KitProperties.
     * 2) Передать запрос в адаптер соседней Packaging-фичи.
     * 3) Вернуть локальный DTO упаковки для расчёта веса и связи kit.pack_dimension_id.
     *
     * @param  array<int, NomenclatureData>  $nomenclatures
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionDTO;
}
