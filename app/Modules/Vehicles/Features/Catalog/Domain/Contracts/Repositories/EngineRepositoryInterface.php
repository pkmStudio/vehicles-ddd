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
     */
    public function findByEngId(int $engId): ?EngineData;

    /**
     * Возвращает ids связок двигателя с модификациями.
     *
     * @return Collection<int, int>
     */
    public function findEngineModificationIdsByEngineId(int $engineId): Collection;
}
