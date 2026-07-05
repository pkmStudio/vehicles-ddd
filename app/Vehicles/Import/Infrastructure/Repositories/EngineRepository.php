<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Import\Infrastructure\Models\Engine;
use Illuminate\Support\Collection;

final readonly class EngineRepository implements EngineRepositoryInterface
{
    public function find(int $id): ?EngineData
    {
        return EngineData::optional(Engine::query()->find($id));
    }

    public function findOrFail(int $id): EngineData
    {
        return EngineData::from(Engine::query()->findOrFail($id));
    }

    public function all(): Collection
    {
        return EngineData::collect(Engine::query()->get(), Collection::class);
    }

    public function firstByEngId(int $engId): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('eng_id', $engId)->first());
    }

    public function firstByCodeEngine(string $code): ?EngineData
    {
        return EngineData::optional(Engine::query()->where('code_engine', $code)->first());
    }
}
