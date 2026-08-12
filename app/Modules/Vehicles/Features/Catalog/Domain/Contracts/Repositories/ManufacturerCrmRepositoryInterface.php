<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerCrmReadQueryDTO;

/**
 * Описывает read port производителей для CRM API.
 */
interface ManufacturerCrmRepositoryInterface
{
    public function paginate(ManufacturerCrmReadQueryDTO $query): ManufacturerCrmPageDTO;

    public function findById(int $id): ?ManufacturerCrmListItemDTO;
}
