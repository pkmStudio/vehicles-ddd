<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Vehicle;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\DeleteVehicleRequestDTO;

interface DeleteVehicleUseCaseInterface
{
    public function execute(DeleteVehicleRequestDTO $request): ?CatalogMutationResultDTO;
}
