<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Import\Domain\ModelData\VehicleData;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;

final readonly class VehicleCommand implements VehicleCommandInterface
{
    public function upsertByMsId(VehicleData $data): VehicleData
    {
        return VehicleData::from(
            Vehicle::query()->updateOrCreate(
                ['ms_id' => $data->msId],
                Arr::except($data->toArray(), ['id']),
            ),
        );
    }
}
