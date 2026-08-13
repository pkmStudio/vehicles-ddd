<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmFeatureValueOptionDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmListItemDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
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
    public function findById(int $id): ?VehicleCrmListItemDTO;

    /**
     * Возвращает CRM projection модификаций автомобиля.
     *
     * Шаги:
     * 1. Принять id автомобиля и read-query DTO.
     * 2. Вернуть page DTO связанных модификаций.
     */
    public function modifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

    /**
     * Возвращает CRM projection двигателей автомобиля.
     *
     * Шаги:
     * 1. Принять id автомобиля и read-query DTO.
     * 2. Вернуть page DTO двигателей с `modification_id`.
     */
    public function engines(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

    /**
     * Возвращает CRM projection спецификаций деталей автомобиля.
     *
     * Шаги:
     * 1. Принять id автомобиля и read-query DTO.
     * 2. Вернуть page DTO связанных спецификаций деталей.
     */
    public function partSpecifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

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
}
