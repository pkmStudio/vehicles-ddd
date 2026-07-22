<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Engine;

final readonly class EngineRepository implements EngineRepositoryInterface
{
    public function findByEngId(int $engId): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('eng_id', $engId)->first());
    }

    public function findByCodeEngine(string $code): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('code_engine', $code)->first());
    }
}
