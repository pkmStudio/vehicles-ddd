<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\UseCases\Manufacturer;

use App\Vehicles\Catalog\Domain\DTOs\CatalogMutationResultDTO;
use App\Vehicles\Catalog\Domain\DTOs\Manufacturer\DeleteManufacturerRequestDTO;

interface DeleteManufacturerUseCaseInterface
{
    public function execute(DeleteManufacturerRequestDTO $request): ?CatalogMutationResultDTO;
}
