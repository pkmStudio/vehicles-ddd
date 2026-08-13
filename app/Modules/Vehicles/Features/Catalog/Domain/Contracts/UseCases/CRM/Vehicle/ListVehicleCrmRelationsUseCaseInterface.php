<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;

/**
 * Use case port relation lists CRM API Vehicles.
 */
interface ListVehicleCrmRelationsUseCaseInterface
{
    /**
     * Возвращает страницу модификаций автомобиля.
     *
     * Шаги:
     * 1. Принять id автомобиля и read-query DTO.
     * 2. Вернуть page DTO relation endpoint.
     */
    public function modifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

    /**
     * Возвращает страницу двигателей автомобиля.
     *
     * Шаги:
     * 1. Принять id автомобиля и read-query DTO.
     * 2. Вернуть page DTO relation endpoint.
     */
    public function engines(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;

    /**
     * Возвращает страницу спецификаций деталей автомобиля.
     *
     * Шаги:
     * 1. Принять id автомобиля и read-query DTO.
     * 2. Вернуть page DTO relation endpoint.
     */
    public function partSpecifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO;
}
