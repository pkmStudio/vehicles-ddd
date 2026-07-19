<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;

final readonly class VehicleCommand implements VehicleCommandInterface
{
    private const array NON_WRITABLE_FIELDS = ['id'];

    public function upsertByMsId(VehicleData $data): VehicleData
    {
        return VehicleData::from(
            Vehicle::query()->updateOrCreate(
                ['ms_id' => $data->msId],
                Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS),
            ),
        );
    }
}
