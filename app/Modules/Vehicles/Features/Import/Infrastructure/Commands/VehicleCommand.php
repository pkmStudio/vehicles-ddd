<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;

final readonly class VehicleCommand implements VehicleCommandInterface
{
    private const array NON_WRITABLE_FIELDS = ['id', 'parent_ms_id'];

    public function create(VehicleData $data): VehicleData
    {
        return VehicleData::from(
            Vehicle::query()->create(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS)),
        );
    }

    public function updateByMsId(VehicleData $data): VehicleData
    {
        $vehicle = Vehicle::query()->where('ms_id', $data->msId)->firstOrFail();
        $vehicle->update(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS));

        return VehicleData::from($vehicle->refresh());
    }
}
