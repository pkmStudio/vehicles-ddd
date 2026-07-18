<?php

declare(strict_types=1);

namespace App\Warehouse\Maintenance\Domain\Contracts\Clients;

use App\Warehouse\Maintenance\Domain\DTOs\KitProperties\KitPropertiesDTO;

interface KitPropertiesClientInterface
{
    /**
     * @param  array<int, \App\Warehouse\Maintenance\Infrastructure\Models\Nomenclature>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
