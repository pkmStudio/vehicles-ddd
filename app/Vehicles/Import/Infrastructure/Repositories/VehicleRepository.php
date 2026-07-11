<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\VehicleData;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;

final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    public function firstByMsId(int $msId): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->where('ms_id', $msId)->first());
    }

    public function minMsId(): int
    {
        return (int) Vehicle::query()->min('ms_id');
    }

    public function parentMsId(int $msId): ?int
    {
        $vehicle = Vehicle::query()->where('ms_id', $msId)->with('parent')->first();

        return $vehicle?->parent?->ms_id;
    }
}
