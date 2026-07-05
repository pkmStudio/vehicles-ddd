<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\Vehicle\VehicleData;
use App\Vehicles\Import\Infrastructure\Models\Vehicle;
use Illuminate\Support\Collection;

final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    public function find(int $id): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->find($id));
    }

    public function findOrFail(int $id): VehicleData
    {
        return VehicleData::from(Vehicle::query()->findOrFail($id));
    }

    public function all(): Collection
    {
        return VehicleData::collect(Vehicle::query()->get(), Collection::class);
    }

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
