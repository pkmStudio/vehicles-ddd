<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Manufacturer\Crm\ManufacturerCrmListItemDTO;

/**
 * Описывает CRM read-сценарий detail-снимка производителя.
 */
interface ShowManufacturerForCrmUseCaseInterface
{
    public function execute(int $id): ?ManufacturerCrmListItemDTO;
}
