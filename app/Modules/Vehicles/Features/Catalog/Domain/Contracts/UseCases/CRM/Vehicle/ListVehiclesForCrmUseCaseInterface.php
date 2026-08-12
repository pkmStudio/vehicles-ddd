<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;

/**
 * Use case port списка CRM API Vehicles.
 */
interface ListVehiclesForCrmUseCaseInterface
{
    /**
     * Возвращает постраничный список ТС для CRM.
     *
     * Шаги:
     * 1) Принять нормализованный DTO фильтрации, сортировки и пагинации.
     * 2) Вернуть page DTO с list items и pagination meta.
     */
    public function execute(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO;
}
