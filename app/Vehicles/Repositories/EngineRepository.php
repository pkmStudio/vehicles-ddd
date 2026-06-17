<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories;

use App\Vehicles\Models\Engine;
use App\Vehicles\Repositories\Contracts\EngineRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EngineRepository implements EngineRepositoryInterface
{
    public function find(int $id): ?Engine
    {
        return Engine::query()->find($id);
    }

    public function findOrFail(int $id): Engine
    {
        return Engine::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return Engine::query()->get();
    }

    public function firstWhere(string $column, mixed $value): ?Engine
    {
        return Engine::query()->where($column, $value)->first();
    }
}
