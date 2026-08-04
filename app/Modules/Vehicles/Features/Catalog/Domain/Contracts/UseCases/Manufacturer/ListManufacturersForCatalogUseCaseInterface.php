<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use Illuminate\Support\Collection;

/**
 * Use case port REST-списка производителей публичного каталога.
 */
interface ListManufacturersForCatalogUseCaseInterface
{
    /** @return Collection<int, CatalogManufacturerDTO> */
    public function execute(): Collection;
}
