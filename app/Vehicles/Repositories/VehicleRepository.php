<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories;

use App\Vehicles\Models\Vehicle;
use App\Vehicles\Repositories\Contracts\VehicleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class VehicleRepository implements VehicleRepositoryInterface
{
    public function find(int $id): ?Vehicle
    {
        return Vehicle::query()->find($id);
    }

    public function findOrFail(int $id): Vehicle
    {
        return Vehicle::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return Vehicle::query()->get();
    }

    public function firstByMsId(int $msId): ?Vehicle
    {
        return Vehicle::query()->where('ms_id', $msId)->first();
    }
}
