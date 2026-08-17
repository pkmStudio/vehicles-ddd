<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
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
     * Возвращает внешние eng_id TD-связей двигателей с модификацией.
     *
     * Шаги:
     * 1. Фильтрует pivot-записи по внутреннему id модификации.
     * 2. Оставляет только связи с provider=TD.
     * 3. Возвращает отсортированную collection eng_id.
     *
     * @return Collection<int, int>
     */
    public function findTdEngineExternalIdsByModificationId(int $modificationId): Collection
    {
        return EngineModification::query()
            ->where('engine_modification.modification_id', $modificationId)
            ->where('engine_modification.provider', ProviderEnum::TD->value)
            ->pluck('engine_modification.eng_id')
            ->sort()
            ->values();
    }

    /**
     * Возвращает ids модификаций по vehicle ids.
     *
     * Шаги:
     * 1. Возвращает пустую collection для пустого списка vehicle ids.
     * 2. Фильтрует Modifications по `vehicle_id`.
     * 3. Возвращает найденные ids.
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
            ->values();
    }

    /**
     * Возвращает ids связок модификаций с двигателями.
     *
     * Шаги:
     * 1. Возвращает пустую collection для пустого списка modification ids.
     * 2. Фильтрует связки по `modification_id`.
     * 3. Возвращает найденные ids.
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
            ->values();
    }
}
