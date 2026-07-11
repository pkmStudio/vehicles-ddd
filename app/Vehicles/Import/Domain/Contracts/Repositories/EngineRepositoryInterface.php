<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Repositories;

use App\Vehicles\Import\Domain\ModelData\EngineData;

interface EngineRepositoryInterface
{
    public function firstByEngId(int $engId): ?EngineData;

    public function firstByCodeEngine(string $code): ?EngineData;
}
