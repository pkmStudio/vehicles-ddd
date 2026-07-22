<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;

final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    public function findByMsId(int $msId): ?VehicleData
    {
        $vehicle = Vehicle::query()
            ->with('parent')
            ->where('ms_id', $msId)
            ->first();

        if ($vehicle === null) {
            return null;
        }

        return VehicleData::from([
            ...$vehicle->toArray(),
            'parent_ms_id' => $vehicle->parent?->ms_id,
        ]);
    }

    public function findMinMsId(): ?VehicleData
    {
        return VehicleData::optional(
            Vehicle::query()
                ->orderBy('ms_id')
                ->first(),
        );
    }

}
