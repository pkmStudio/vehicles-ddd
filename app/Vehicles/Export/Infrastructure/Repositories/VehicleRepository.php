<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Repositories;

use App\Vehicles\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Export\Domain\ModelData\Vehicle\VehicleData;
use App\Vehicles\Export\Infrastructure\Models\Vehicle;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
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

    public function forMainSheet(bool $onlyAllowed): Collection
    {
        $vehicles = Vehicle::query()
            ->with(['manufacturer', 'parent'])
            ->when($onlyAllowed, fn ($q) => $q->where('is_allow', true))
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }

    public function forWiperSheet(bool $onlyAllowed): Collection
    {
        $vehicles = Vehicle::query()
            ->with([
                'manufacturer',
                'parent',
                'partSpecifications' => fn ($q) => $q
                    ->where('template', DetailTemplateEnum::WIPER)
                    ->with('featureValue'),
            ])
            ->when($onlyAllowed, fn ($q) => $q->where('is_allow', true))
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }
}
