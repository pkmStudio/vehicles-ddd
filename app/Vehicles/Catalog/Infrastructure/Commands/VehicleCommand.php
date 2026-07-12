<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Commands;

use App\Vehicles\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Catalog\Domain\ModelData\VehicleData;
use App\Vehicles\Catalog\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class VehicleCommand implements VehicleCommandInterface
{
    public function create(VehicleData $data): VehicleData
    {
        return DB::transaction(
            fn (): VehicleData => VehicleData::from(
                Vehicle::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    public function update(VehicleData $data): VehicleData
    {
        return DB::transaction(function () use ($data): VehicleData {
            $vehicle = Vehicle::query()->where('ms_id', $data->msId)->firstOrFail();
            $vehicle->fill(Arr::except($data->toArray(), ['id']));
            $vehicle->save();

            return VehicleData::from($vehicle->refresh());
        });
    }

    public function deleteByMsId(int $msId): void
    {
        DB::transaction(function () use ($msId): void {
            Vehicle::query()->where('ms_id', $msId)->delete();
        });
    }
}
