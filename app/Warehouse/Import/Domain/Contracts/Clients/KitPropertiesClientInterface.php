<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Clients;

use App\Warehouse\Import\Domain\DTOs\KitProperties\KitPropertiesDTO;

interface KitPropertiesClientInterface
{
    /**
     * @param  array<int, \App\Warehouse\Import\Domain\ModelData\NomenclatureData>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
