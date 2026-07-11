<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Import\Domain\ModelData\VehicleData;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;

final readonly class VehicleCommand implements VehicleCommandInterface
{
    public function create(VehicleData $data): VehicleData
    {
        return VehicleData::from(
            Vehicle::query()->create(Arr::except($data->toArray(), ['id'])),
        );
    }

    public function update(VehicleData $data): VehicleData
    {
        $vehicle = Vehicle::query()->findOrFail($data->id);
        $vehicle->update(Arr::except($data->toArray(), ['id']));

        return VehicleData::from($vehicle);
    }

    public function upsertByMsId(VehicleData $data): VehicleData
    {
        return VehicleData::from(
            Vehicle::query()->updateOrCreate(
                ['ms_id' => $data->msId],
                Arr::except($data->toArray(), ['id']),
            ),
        );
    }

    public function delete(VehicleData $data): bool
    {
        return (bool) Vehicle::query()->whereKey($data->id)->delete();
    }
}
