<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\KitProperties\KitPropertiesDTO;

interface KitPropertiesClientInterface
{
    /**
     * @param  array<int, \App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
