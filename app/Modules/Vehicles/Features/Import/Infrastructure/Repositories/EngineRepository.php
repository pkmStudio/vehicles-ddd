<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;

/**
 * Читает engine snapshots для import-сценариев Vehicles.
 */
final readonly class EngineRepository implements EngineRepositoryInterface
{
    /**
     * Ищет двигатель по TecDoc `eng_id`.
     *
     * Шаги:
     * 1) Делегировать поиск общему column lookup.
     * 2) Вернуть typed `EngineData` или null.
     */
    public function findByEngId(int $engId): ?EngineData
    {
        return $this->findByColumn('eng_id', $engId);
    }

    /**
     * Ищет двигатель по коду двигателя.
     *
     * Шаги:
     * 1) Делегировать поиск общему column lookup.
     * 2) Вернуть typed `EngineData` или null.
     */
    public function findByCodeEngine(string $code): ?EngineData
    {
        return $this->findByColumn('code_engine', $code);
    }

    /**
     * Выполняет общий точечный lookup двигателя по колонке.
     *
     * Шаги:
     * 1) Отфильтровать engine-модель по указанной колонке и значению.
     * 2) Сконвертировать найденную Eloquent-модель в optional `EngineData`.
     */
    private function findByColumn(string $column, int|string $value): ?EngineData
    {
        return EngineData::optional(
            Engine::query()
                ->where($column, $value)
                ->first(),
        );
    }
}
