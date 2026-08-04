<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services;

interface CatalogCascadeDeleteServiceInterface
{
    public function deleteVehiclesByManufacturerId(int $manufacturerId): void;

    /**
     * @param  array<int, int>  $vehicleIds
     */
    public function deleteVehiclesByIds(array $vehicleIds): void;

    /**
     * @param  array<int, int>  $modificationIds
     */
    public function deleteModificationDependencies(array $modificationIds): void;

    public function deleteEngineDependencies(int $engineId): void;
}
