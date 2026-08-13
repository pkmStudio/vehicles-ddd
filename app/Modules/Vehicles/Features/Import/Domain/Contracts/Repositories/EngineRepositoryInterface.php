<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface EngineRepositoryInterface
{
    /**
     * Найти двигатель по внешнему eng_id.
     *
     * Шаги:
     * 1) Выполнить read query по eng_id.
     * 2) Вернуть EngineData или null.
     */
    public function findByEngId(int $engId): ?EngineData;

    /**
     * Найти двигатель по строковому коду.
     *
     * Шаги:
     * 1) Выполнить read query по code_engine.
     * 2) Вернуть EngineData или null.
     */
    public function findByCodeEngine(string $code): ?EngineData;
}
