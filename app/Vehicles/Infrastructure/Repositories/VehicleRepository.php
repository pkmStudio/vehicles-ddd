<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Vehicles\Domain\Enums\DetailTemplateEnum;
use App\Vehicles\Domain\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

final readonly class VehicleRepository implements VehicleRepositoryInterface
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

    public function minMsId(): int
    {
        return (int) Vehicle::query()->min('ms_id');
    }

    public function forMainSheet(bool $onlyAllowed): Collection
    {
        return Vehicle::query()
            ->with(['manufacturer', 'parent'])
            ->when($onlyAllowed, fn ($q) => $q->where('is_allow', true))
            ->get();
    }

    public function forWiperSheet(bool $onlyAllowed): Collection
    {
        return Vehicle::query()
            ->with([
                'manufacturer',
                'parent',
                'partSpecifications' => fn ($q) => $q
                    ->where('template', DetailTemplateEnum::WIPER)
                    ->with('featureValue'),
            ])
            ->when($onlyAllowed, fn ($q) => $q->where('is_allow', true))
            ->get();
    }
}
