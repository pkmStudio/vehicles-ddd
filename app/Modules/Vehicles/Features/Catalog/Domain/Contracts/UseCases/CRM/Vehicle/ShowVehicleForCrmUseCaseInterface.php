<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;

/**
 * Use case port detail CRM API Vehicles.
 */
interface ShowVehicleForCrmUseCaseInterface
{
    /**
     * Возвращает detail projection автомобиля или null.
     */
    public function execute(int $id): ?VehicleCrmDetailDTO;
}
