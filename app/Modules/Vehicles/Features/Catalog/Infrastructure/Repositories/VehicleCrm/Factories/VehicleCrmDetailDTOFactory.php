<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories\VehicleCrm\Factories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use Illuminate\Support\Collection;

/**
 * Собирает detail DTO автомобиля для CRM read API.
 */
final readonly class VehicleCrmDetailDTOFactory
{
    /**
     * Создает detail DTO из root vehicle DTO и вложенных коллекций.
     *
     * Шаги:
     * 1. Принимает list item DTO автомобиля.
     * 2. Принимает уже собранные modifications и part specifications.
     * 3. Возвращает aggregate detail DTO для CRM boundary.
     *
     * @param  Collection<int, mixed>  $modifications
     * @param  Collection<int, mixed>  $partSpecifications
     */
    public function make(
        VehicleCrmListItemDTO $vehicle,
        Collection $modifications,
        Collection $partSpecifications,
    ): VehicleCrmDetailDTO {
        return new VehicleCrmDetailDTO(
            vehicle: $vehicle,
            modifications: $modifications,
            partSpecifications: $partSpecifications,
        );
    }
}
