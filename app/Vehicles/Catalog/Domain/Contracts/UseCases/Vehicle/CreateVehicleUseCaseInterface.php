<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\CreateVehicleRequestDTO;

interface CreateVehicleUseCaseInterface
{
    public function execute(CreateVehicleRequestDTO $request): ?CatalogMutationResultDTO;
}
