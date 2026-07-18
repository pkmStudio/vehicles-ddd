<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Maintenance\Domain\Contracts\Clients;

use App\Modules\Warehouse\Features\Maintenance\Domain\DTOs\KitProperties\KitPropertiesDTO;

interface KitPropertiesClientInterface
{
    /**
     * @param  array<int, \App\Modules\Warehouse\Features\Maintenance\Infrastructure\Models\Nomenclature>  $nomenclatures
     */
    public function build(array $nomenclatures): KitPropertiesDTO;
}
