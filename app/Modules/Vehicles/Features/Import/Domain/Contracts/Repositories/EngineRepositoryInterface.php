<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;

interface EngineRepositoryInterface
{
    public function findByEngId(int $engId): ?EngineData;

    public function findByCodeEngine(string $code): ?EngineData;
}
