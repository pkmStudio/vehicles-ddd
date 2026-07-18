<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\KitProperties\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\KitProperties\Domain\DTOs\Packaging\PackDimensionDTO;
use App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\TypeData;

interface PackagingClientInterface
{
    /**
     * @param  array<int, \App\Modules\Warehouse\Features\KitProperties\Domain\ModelData\NomenclatureData>  $nomenclatures
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionDTO;
}
