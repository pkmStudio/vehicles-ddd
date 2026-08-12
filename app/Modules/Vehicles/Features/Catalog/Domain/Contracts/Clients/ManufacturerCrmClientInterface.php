<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Clients;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerCrmReadQueryDTO;

/**
 * Описывает read-only клиент CRM сценариев производителей.
 */
interface ManufacturerCrmClientInterface
{
    public function paginate(ManufacturerCrmReadQueryDTO $query): ManufacturerCrmPageDTO;

    public function show(int $id): ?ManufacturerCrmListItemDTO;
}
