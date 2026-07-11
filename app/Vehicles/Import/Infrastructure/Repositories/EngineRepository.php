<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\EngineData;
use App\Vehicles\Import\Infrastructure\Models\Engine;

final readonly class EngineRepository implements EngineRepositoryInterface
{
    public function firstByEngId(int $engId): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('eng_id', $engId)->first());
    }

    public function firstByCodeEngine(string $code): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('code_engine', $code)->first());
    }
}
