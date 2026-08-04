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
     */
    public function findByEngId(int $engId): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('eng_id', $engId)->first());
    }

    /**
     * Возвращает ids связок двигателя с модификациями.
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

    private function toInteger(mixed $id): int
    {
        return (int) $id;
    }
}
