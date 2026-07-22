<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleDeletionBlockersDTO;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

/**
 * Читает автомобилей через Eloquent-модель фичи Catalog.
 */
final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     */
    public function findByMsId(int $msId): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->where('ms_id', $msId)->first());
    }

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function vehicleIdByMsId(int $msId): ?int
    {
        $id = Vehicle::query()->where('ms_id', $msId)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function manufacturerIdByMfaId(int $mfaId): ?int
    {
        $id = Manufacturer::query()->where('mfa_id', $mfaId)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Собирает зависимости, блокирующие удаление автомобилей.
     *
     * Шаги:
     * 1) Найти целевую запись по внешнему идентификатору.
     * 2) Посчитать связанные записи, которые нельзя удалить каскадом.
     * 3) Вернуть DTO или массив блокировок удаления.
     */
    public function deletionBlockersByMsId(int $msId): ?VehicleDeletionBlockersDTO
    {
        $vehicleId = $this->vehicleIdByMsId($msId);
        if ($vehicleId === null) {
            return null;
        }

        return new VehicleDeletionBlockersDTO(
            childrenCount: Vehicle::query()->where('parent_id', $vehicleId)->count(),
            modificationsCount: Modification::query()->where('vehicle_id', $vehicleId)->count(),
            partSpecificationsCount: PartSpecification::query()
                ->where('partable_type', PartableTypeEnum::VEHICLE->value)
                ->where('partable_id', $vehicleId)
                ->count(),
        );
    }
}
