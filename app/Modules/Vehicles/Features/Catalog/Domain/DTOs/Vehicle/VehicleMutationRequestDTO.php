<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Enums\CatalogMutationOperationEnum;

/**
 * Передает параметры сценария или результат мутации автомобилей.
 */
final readonly class VehicleMutationRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateVehicleRequestDTO|UpdateVehicleRequestDTO|DeleteVehicleRequestDTO $request,
    ) {}
}
