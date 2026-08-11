<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Engine;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
use Illuminate\Support\Collection;

/**
 * Читает двигателей через Eloquent-модель фичи Catalog.
 */
final readonly class EngineRepository implements EngineRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1. Фильтрует Engines по внешнему `eng_id`.
     * 2. Берет первую найденную запись.
     * 3. Преобразует модель в `EngineData` или возвращает `null`.
     */
    public function findByEngId(int $engId): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('eng_id', $engId)->first());
    }

    /**
     * Возвращает ids связок двигателя с модификациями.
     *
     * Шаги:
     * 1. Фильтрует связки по внутреннему id двигателя.
     * 2. Забирает только колонку `id`.
     * 3. Нормализует значения в integer collection.
     *
     * @return Collection<int, int>
     */
    public function findEngineModificationIdsByEngineId(int $engineId): Collection
    {
        return EngineModification::query()
            ->where('engine_id', $engineId)
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
