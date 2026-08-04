<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Repository-порт CRM API каталога Vehicles.
 */
interface VehicleCrmRepositoryInterface
{
    /**
     * Возвращает постраничную CRM projection автомобилей.
     */
    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO;

    /**
     * Возвращает detail projection автомобиля или null.
     */
    public function findById(int $id): ?VehicleCrmDetailDTO;

    /**
     * Возвращает compact search options автомобилей.
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection;

    /**
     * Возвращает feature options.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function featureOptions(): Collection;

    /**
     * Возвращает feature value options.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValueOptions(int $featureId): Collection;

    /**
     * Возвращает manufacturer options.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturerOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
