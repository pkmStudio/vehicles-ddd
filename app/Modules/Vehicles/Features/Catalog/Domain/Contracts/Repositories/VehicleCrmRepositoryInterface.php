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
     *
     * Шаги:
     * 1. Принять read-query DTO.
     * 2. Вернуть page DTO, совместимый с CRM boundary.
     */
    public function paginate(VehicleCrmReadQueryDTO $query): VehicleCrmPageDTO;

    /**
     * Возвращает detail projection автомобиля или null.
     *
     * Шаги:
     * 1. Принять внутренний id автомобиля.
     * 2. Вернуть detail DTO или `null`, если запись не найдена.
     */
    public function findById(int $id): ?VehicleCrmDetailDTO;

    /**
     * Возвращает compact search options автомобилей.
     *
     * Шаги:
     * 1. Принять строку поиска и limit.
     * 2. Вернуть collection search DTO.
     *
     * @return Collection<int, VehicleCrmSearchItemDTO>
     */
    public function search(string $query, int $limit = 20): Collection;

    /**
     * Возвращает feature options.
     *
     * Шаги:
     * 1. Прочитать доступные характеристики автомобилей.
     * 2. Вернуть collection option DTO.
     *
     * @return Collection<int, VehicleCrmFeatureOptionDTO>
     */
    public function featureOptions(): Collection;

    /**
     * Возвращает feature value options.
     *
     * Шаги:
     * 1. Принять id характеристики.
     * 2. Вернуть collection option DTO значений характеристики.
     *
     * @return Collection<int, VehicleCrmFeatureValueOptionDTO>
     */
    public function featureValueOptions(int $featureId): Collection;

    /**
     * Возвращает manufacturer options.
     *
     * Шаги:
     * 1. Принять optional search query, selected id и limit.
     * 2. Вернуть collection option DTO производителей.
     *
     * @return Collection<int, VehicleCrmManufacturerOptionDTO>
     */
    public function manufacturerOptions(?string $query = null, ?int $id = null, int $limit = 50): Collection;
}
