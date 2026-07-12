<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Repositories;

use App\Vehicles\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleDeletionBlockersDTO;
use App\Vehicles\Catalog\Domain\ModelData\VehicleData;
use App\Vehicles\Catalog\Infrastructure\Models\Manufacturer;
use App\Vehicles\Catalog\Infrastructure\Models\Modification;
use App\Vehicles\Catalog\Infrastructure\Models\PartSpecification;
use App\Vehicles\Catalog\Infrastructure\Models\Vehicle;
use App\Vehicles\Shared\Domain\Enums\PartableTypeEnum;

final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    public function firstByMsId(int $msId): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->where('ms_id', $msId)->first());
    }

    public function vehicleIdByMsId(int $msId): ?int
    {
        $id = Vehicle::query()->where('ms_id', $msId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function manufacturerIdByMfaId(int $mfaId): ?int
    {
        $id = Manufacturer::query()->where('mfa_id', $mfaId)->value('id');

        return $id === null ? null : (int) $id;
    }

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
