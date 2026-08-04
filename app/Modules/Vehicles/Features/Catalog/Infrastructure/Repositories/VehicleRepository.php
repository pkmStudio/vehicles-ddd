<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Читает автомобилей через Eloquent-модель фичи Catalog.
 */
final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Возвращает ТС по внутреннему идентификатору.
     */
    public function findById(int $id): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->find($id));
    }

    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     */
    public function findByMsId(int $msId): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->where('ms_id', $msId)->first());
    }

    /**
     * Возвращает разрешённые ТС производителя.
     *
     * @return Collection<int, VehicleData>
     */
    public function findAllowedByManufacturerId(int $manufacturerId): Collection
    {
        $vehicles = Vehicle::query()
            ->where('manufacturer_id', $manufacturerId)
            ->where('is_allow', true)
            ->orderBy('name')
            ->orderBy('generation_year_from')
            ->orderBy('id')
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }

    /**
     * Возвращает ids ТС производителя.
     *
     * @return Collection<int, int>
     */
    public function findIdsByManufacturerId(int $manufacturerId): Collection
    {
        return Vehicle::query()
            ->where('manufacturer_id', $manufacturerId)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    /**
     * Возвращает ids дочерних ТС по parent ids.
     *
     * @param  array<int, int>  $parentIds
     * @return Collection<int, int>
     */
    public function findChildIdsByParentIds(array $parentIds): Collection
    {
        if ($parentIds === []) {
            return collect();
        }

        return Vehicle::query()
            ->whereIn('parent_id', $parentIds)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    /**
     * Возвращает следующий свободный внешний идентификатор автомобиля.
     */
    public function nextMsId(): int
    {
        $minMsId = Vehicle::query()->min('ms_id');

        return min((int) ($minMsId ?? 0), 0) - 1;
    }

    private function toInteger(mixed $id): int
    {
        return (int) $id;
    }
}
