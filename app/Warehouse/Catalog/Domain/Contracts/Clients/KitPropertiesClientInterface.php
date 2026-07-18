<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Domain\Contracts\Clients;

use App\Warehouse\Catalog\Domain\DTOs\KitProperties\KitPropertiesDTO;

interface KitPropertiesClientInterface
{
    /**
     * @param  array<int, \App\Warehouse\Catalog\Domain\ModelData\NomenclatureData>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
