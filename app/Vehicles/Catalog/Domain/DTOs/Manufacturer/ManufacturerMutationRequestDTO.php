<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Manufacturer;

use App\Vehicles\Catalog\Domain\Enums\CatalogMutationOperationEnum;

final readonly class ManufacturerMutationRequestDTO
{
    public function __construct(
        public CatalogMutationOperationEnum $operation,
        public CreateManufacturerRequestDTO|UpdateManufacturerRequestDTO|DeleteManufacturerRequestDTO $request,
    ) {}
}
