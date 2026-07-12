<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Repositories;

use App\Vehicles\Catalog\Domain\ModelData\EngineData;

/**
 * Описывает порт чтения двигателей из каталога.
 */
interface EngineRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок двигателей по внешнему идентификатору.
     */
    public function firstByEngId(int $engId): ?EngineData;

    /**
     * @return array{engine_modifications_count: int, part_specifications_count: int}|null
     */
    public function deletionBlockersByEngId(int $engId): ?array;
}
