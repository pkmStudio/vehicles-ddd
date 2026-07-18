<?php

declare(strict_types=1);

namespace App\Warehouse\KitProperties\Domain\Contracts\Clients;

use App\Warehouse\KitProperties\Domain\DTOs\Packaging\PackDimensionDTO;
use App\Warehouse\KitProperties\Domain\ModelData\TypeData;

interface PackagingClientInterface
{
    /**
     * @param  array<int, \App\Warehouse\KitProperties\Domain\ModelData\NomenclatureData>  $nomenclatures
     */
    public function selectOrCreate(TypeData $type, array $nomenclatures): PackDimensionDTO;
}
