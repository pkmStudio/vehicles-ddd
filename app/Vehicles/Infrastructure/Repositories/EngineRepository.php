<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Enums\Templates\DetailTemplateEnum;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Contracts\Infrastructure\Repositories\EngineRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final readonly class EngineRepository implements EngineRepositoryInterface
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

    public function firstByEngId(int $engId): ?Engine
    {
        return Engine::query()->where('eng_id', $engId)->first();
    }

    public function firstByCodeEngine(string $code): ?Engine
    {
        return Engine::query()->where('code_engine', $code)->first();
    }

    public function forSparkPlugSheet(): Collection
    {
        return Engine::query()
            ->with([
                'partSpecifications' => fn ($q) => $q->where('template', DetailTemplateEnum::SPARK_PLUGS),
            ])
            ->get();
    }
}
