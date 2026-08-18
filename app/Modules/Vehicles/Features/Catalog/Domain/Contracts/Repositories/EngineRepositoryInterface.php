<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\EngineData;
use Illuminate\Support\Collection;

/**
 * Описывает порт чтения двигателей из каталога.
 */
interface EngineRepositoryInterface
{
    /**
     * Возвращает двигатель по внутреннему id или null.
     *
     * Шаги:
     * 1. Принять внутренний id двигателя.
     * 2. Вернуть `EngineData` или `null`, если запись не найдена.
     */
    public function findById(int $id): ?EngineData;

    /**
     * Возвращает первый Data-снимок двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1. Принять внешний `eng_id` двигателя.
     * 2. Вернуть первый `EngineData` или `null`, если запись не найдена.
     */
    public function findByEngId(int $engId): ?EngineData;

    /**
     * Возвращает двигатели по внешним eng_id, индексированные по eng_id.
     *
     * Шаги:
     * 1. Принять список внешних `eng_id` двигателей.
     * 2. Вернуть найденные `EngineData`, индексированные по `eng_id`.
     *
     * @param  list<int>  $engIds
     * @return Collection<int, EngineData>
     */
    public function findByEngIds(array $engIds): Collection;

    /**
     * Возвращает двигатели по внутренним id, индексированные по id.
     *
     * Шаги:
     * 1. Принять список внутренних id двигателей.
     * 2. Вернуть найденные `EngineData`, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, EngineData>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Возвращает следующий локальный отрицательный eng_id для двигателя каталога.
     *
     * Шаги:
     * 1) Найти минимальный текущий eng_id в catalog storage.
     * 2) Вернуть следующий отрицательный id для catalog-owned двигателя.
     */
    public function nextOwnEngId(): int;

    /**
     * Возвращает ids связок двигателя с модификациями.
     *
     * Шаги:
     * 1. Принять внутренний id двигателя.
     * 2. Вернуть collection id связок двигателя с модификациями.
     *
     * @return Collection<int, int>
     */
    public function findEngineModificationIdsByEngineId(int $engineId): Collection;
}
