<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Application\UseCases\CRM\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleCrmRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\CRM\Vehicle\ListVehicleCrmRelationsUseCaseInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm\VehicleCrmRelationPageDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;

/**
 * Оркестрирует CRM relation read-сценарии автомобиля.
 */
final readonly class ListVehicleCrmRelationsUseCase implements ListVehicleCrmRelationsUseCaseInterface
{
    public function __construct(
        private VehicleCrmRepositoryInterface $vehicles,
    ) {}

    /**
     * Возвращает страницу модификаций автомобиля для CRM.
     *
     * Шаги:
     * 1. Принимает id автомобиля и read-query DTO.
     * 2. Делегирует чтение relation repository.
     * 3. Возвращает page DTO модификаций.
     */
    public function modifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO
    {
        return $this->vehicles->modifications($vehicleId, $query);
    }

    /**
     * Возвращает страницу двигателей автомобиля для CRM.
     *
     * Шаги:
     * 1. Принимает id автомобиля и read-query DTO.
     * 2. Делегирует чтение relation repository.
     * 3. Возвращает page DTO двигателей с `modification_id`.
     */
    public function engines(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO
    {
        return $this->vehicles->engines($vehicleId, $query);
    }

    /**
     * Возвращает страницу спецификаций деталей автомобиля для CRM.
     *
     * Шаги:
     * 1. Принимает id автомобиля и read-query DTO.
     * 2. Делегирует чтение relation repository.
     * 3. Возвращает page DTO спецификаций деталей.
     */
    public function partSpecifications(int $vehicleId, VehicleCrmReadQueryDTO $query): VehicleCrmRelationPageDTO
    {
        return $this->vehicles->partSpecifications($vehicleId, $query);
    }
}
