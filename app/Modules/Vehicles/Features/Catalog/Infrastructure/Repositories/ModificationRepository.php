<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use Illuminate\Support\Collection;

/**
 * Читает модификаций через Eloquent-модель фичи Catalog.
 */
final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    /**
     * Возвращает модификацию по внутреннему идентификатору.
     *
     * Шаги:
     * 1. Выполняет lookup Modification по primary key.
     * 2. Преобразует найденную модель в `ModificationData`.
     * 3. Возвращает `null`, если запись не найдена.
     */
    public function findById(int $id): ?ModificationData
    {
        return ModificationData::optional(Modification::query()->find($id));
    }

    /**
     * Возвращает первый Data-снимок модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1. Фильтрует Modifications по `mod_id` и типу.
     * 2. Берет первую найденную запись.
     * 3. Преобразует модель в `ModificationData` или возвращает `null`.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData
    {
        return ModificationData::optional(
            Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->first(),
        );
    }

    /**
     * Возвращает следующий локальный отрицательный mod_id.
     *
     * Шаги:
     * - Найти минимальный mod_id среди модификаций.
     * - Сдвинуть значение ниже нуля, чтобы не пересечься с внешними id.
     */
    public function nextOwnModId(): int
    {
        $minModId = (int) (Modification::query()->min('mod_id') ?? 0);

        return min($minModId, 0) - 1;
    }

    /**
     * Возвращает модификации ТС.
     *
     * Шаги:
     * 1. Фильтрует Modifications по внутреннему id автомобиля.
     * 2. Сортирует результат по году начала и id.
     * 3. Преобразует collection моделей в collection `ModificationData`.
     *
     * @return Collection<int, ModificationData>
     */
    public function findByVehicleId(int $vehicleId): Collection
    {
        $modifications = Modification::query()
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('year_from')
            ->orderBy('id')
            ->get();

        return ModificationData::collect($modifications, Collection::class);
    }

    /**
     * Возвращает ids модификаций по vehicle ids.
     *
     * Шаги:
     * 1. Возвращает пустую collection для пустого списка vehicle ids.
     * 2. Фильтрует Modifications по `vehicle_id`.
     * 3. Нормализует найденные ids в integer collection.
     *
     * @param  array<int, int>  $vehicleIds
     * @return Collection<int, int>
     */
    public function findIdsByVehicleIds(array $vehicleIds): Collection
    {
        if ($vehicleIds === []) {
            return collect();
        }

        return Modification::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    /**
     * Возвращает ids связок модификаций с двигателями.
     *
     * Шаги:
     * 1. Возвращает пустую collection для пустого списка modification ids.
     * 2. Фильтрует связки по `modification_id`.
     * 3. Нормализует найденные ids в integer collection.
     *
     * @param  array<int, int>  $modificationIds
     * @return Collection<int, int>
     */
    public function findEngineModificationIdsByModificationIds(array $modificationIds): Collection
    {
        if ($modificationIds === []) {
            return collect();
        }

        return EngineModification::query()
            ->whereIn('modification_id', $modificationIds)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    /**
     * Нормализует id из database scalar в integer.
     *
     * Шаги:
     * 1. Принимает scalar значение id из Eloquent result.
     * 2. Возвращает значение, приведенное к `int`.
     */
    private function toInteger(mixed $id): int
    {
        return (int) $id;
    }
}
