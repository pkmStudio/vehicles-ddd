<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Vehicle;

use App\Vehicles\Catalog\Domain\Enums\VehicleMutationOperationEnum;

final readonly class VehicleMutationRequestDTO
{
    public function __construct(
        public VehicleMutationOperationEnum $operation,
        public CreateVehicleRequestDTO|UpdateVehicleRequestDTO|DeleteVehicleRequestDTO $request,
    ) {}
}
