<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmDetailTemplateOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmManufacturerOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmSearchItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;
use Illuminate\Support\Collection;

/**
 * Read-порт CRM API каталога Vehicles.
 */
interface VehicleCrmReadRepositoryInterface
{
    /**
     * Возвращает постраничную CRM projection автомобилей.
     */
    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO;

    /**
     * Возвращает detail projection автомобиля или null.
     */
    public function find(int $id): ?VehicleCrmDetailDTO;

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
     * Возвращает detail template options.
     *
     * @return Collection<int, VehicleCrmDetailTemplateOptionDTO>
     */
    public function detailTemplateOptions(): Collection;

    /**
     * Возвращает manufacturer options.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturerOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
