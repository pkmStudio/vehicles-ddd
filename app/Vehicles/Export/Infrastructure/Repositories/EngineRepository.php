<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Infrastructure\Repositories;

use App\Vehicles\Export\Domain\Contracts\Repositories\EngineRepositoryInterface;
use App\Vehicles\Export\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Export\Infrastructure\Models\Engine;
use App\Vehicles\Templates\Domain\Enums\DetailTemplateEnum;
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

    public function forSparkPlugSheet(): Collection
    {
        $engines = Engine::query()
            ->with([
                'partSpecifications' => fn ($q) => $q->where('template', DetailTemplateEnum::SPARK_PLUGS),
            ])
            ->get();

        return EngineData::collect($engines, Collection::class);
    }
}
