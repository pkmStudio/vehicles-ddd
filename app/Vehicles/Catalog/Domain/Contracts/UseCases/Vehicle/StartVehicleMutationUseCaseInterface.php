<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleMutationRequestDTO;

interface StartVehicleMutationUseCaseInterface
{
    public function execute(VehicleMutationRequestDTO $request): ?CatalogMutationResultDTO;
}
