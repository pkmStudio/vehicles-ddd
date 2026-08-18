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
     *
     * Шаги:
     * 1. Выполняет lookup Vehicle по primary key.
     * 2. Преобразует найденную модель в `VehicleData`.
     * 3. Возвращает `null`, если запись не найдена.
     */
    public function findById(int $id): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->find($id));
    }

    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     *
     * Шаги:
     * 1. Фильтрует Vehicles по внешнему `ms_id`.
     * 2. Берет первую найденную запись.
     * 3. Преобразует модель в `VehicleData` или возвращает `null`.
     */
    public function findByMsId(int $msId): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->where('ms_id', $msId)->first());
    }

    /**
     * Возвращает ТС по внешним ms_id, индексированные по ms_id.
     *
     * @param  list<int>  $msIds
     * @return Collection<int, VehicleData>
     */
    public function findByMsIds(array $msIds): Collection
    {
        $vehicles = Vehicle::query()
            ->whereIn('ms_id', $msIds)
            ->get();

        return VehicleData::collect($vehicles, Collection::class)->keyBy('msId');
    }

    /**
     * Возвращает ТС по внутренним id, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, VehicleData>
     */
    public function findByIds(array $ids): Collection
    {
        $vehicles = Vehicle::query()
            ->whereIn('id', $ids)
            ->get();

        return VehicleData::collect($vehicles, Collection::class)->keyBy('id');
    }

    /**
     * Возвращает разрешённые ТС производителя.
     *
     * Шаги:
     * 1. Фильтрует Vehicles по производителю и `is_allow=true`.
     * 2. Сортирует результат для стабильного catalog response.
     * 3. Преобразует collection моделей в collection `VehicleData`.
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
     * Шаги:
     * 1. Фильтрует Vehicles по производителю.
     * 2. Забирает только колонку `id`.
     * 3. Возвращает найденные ids.
     *
     * @return Collection<int, int>
     */
    public function findIdsByManufacturerId(int $manufacturerId): Collection
    {
        return Vehicle::query()
            ->where('manufacturer_id', $manufacturerId)
            ->pluck('id')
            ->values();
    }

    /**
     * Возвращает ids дочерних ТС по parent ids.
     *
     * Шаги:
     * 1. Возвращает пустую collection для пустого списка parent ids.
     * 2. Фильтрует Vehicles по `parent_id`.
     * 3. Возвращает найденные ids.
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
            ->values();
    }

    /**
     * Возвращает следующий свободный внешний идентификатор автомобиля.
     *
     * Шаги:
     * 1. Читает минимальный существующий `ms_id`.
     * 2. Берет минимум между найденным id и нулем.
     * 3. Возвращает следующий отрицательный `ms_id`.
     */
    public function nextMsId(): int
    {
        $minMsId = Vehicle::query()->min('ms_id');

        return min((int) ($minMsId ?? 0), 0) - 1;
    }
}
