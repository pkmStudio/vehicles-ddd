<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\ManufacturerCrmReadQueryDTO;

/**
 * Описывает CRM read-сценарий списка производителей.
 */
interface ListManufacturersForCrmUseCaseInterface
{
    public function execute(ManufacturerCrmReadQueryDTO $query): ManufacturerCrmPageDTO;
}
