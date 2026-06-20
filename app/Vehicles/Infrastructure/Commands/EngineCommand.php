<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Domain\Models\Engine;
use App\Vehicles\Domain\Contracts\Infrastructure\Commands\EngineCommandInterface;

final readonly class EngineCommand implements EngineCommandInterface
{
    public function create(EngineData $data): Engine
    {
        return Engine::query()->create($data->toArray());
    }

    public function update(Engine $engine, EngineData $data): Engine
    {
        $engine->update($data->toArray());

        return $engine;
    }

    public function upsertByEngId(EngineData $data): Engine
    {
        return Engine::query()->updateOrCreate(
            ['eng_id' => $data->engId],
            $data->toArray(),
        );
    }

    public function updateEditableByEngId(int $engId, array $attributes): Engine
    {
        return Engine::query()->updateOrCreate(
            ['eng_id' => $engId],
            $attributes,
        );
    }

    public function setGroupId(Engine $engine, int $groupId): void
    {
        $engine->update(['group_id' => $groupId]);
    }

    public function delete(Engine $engine): bool
    {
        return (bool) $engine->delete();
    }
}
