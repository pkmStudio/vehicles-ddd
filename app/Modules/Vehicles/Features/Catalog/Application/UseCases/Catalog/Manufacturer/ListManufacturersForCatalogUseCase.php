<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\Catalog\Manufacturer;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Catalog\Manufacturer\ListManufacturersForCatalogUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Catalog\CatalogManufacturerDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use Illuminate\Support\Collection;

/**
 * Возвращает REST-список производителей публичного каталога.
 */
final readonly class ListManufacturersForCatalogUseCase implements ListManufacturersForCatalogUseCaseInterface
{
    public function __construct(
        private ManufacturerRepositoryInterface $manufacturers,
    ) {}

    /** @return Collection<int, CatalogManufacturerDTO> */
    public function execute(): Collection
    {
        return $this->manufacturers
            ->findAllWithAllowedVehicles()
            ->map(static fn (ManufacturerData $manufacturer): CatalogManufacturerDTO => CatalogManufacturerDTO::fromData($manufacturer))
            ->values();
    }
}
