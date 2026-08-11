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
     * Возвращает первый Data-снимок двигателей по внешнему идентификатору.
     *
     * Шаги:
     * 1. Принять внешний `eng_id` двигателя.
     * 2. Вернуть первый `EngineData` или `null`, если запись не найдена.
     */
    public function findByEngId(int $engId): ?EngineData;

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
