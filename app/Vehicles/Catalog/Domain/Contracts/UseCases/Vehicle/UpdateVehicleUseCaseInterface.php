<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\UpdateVehicleRequestDTO;

interface UpdateVehicleUseCaseInterface
{
    public function execute(UpdateVehicleRequestDTO $request): ?CatalogMutationResultDTO;
}
