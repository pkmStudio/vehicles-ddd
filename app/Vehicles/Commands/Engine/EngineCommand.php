<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Engine;

use App\Vehicles\Commands\Engine\EngineCommandInterface;
use App\Vehicles\DTOs\Engine\EngineData;
use App\Vehicles\Models\Engine;

final class EngineCommand implements EngineCommandInterface
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

    public function delete(Engine $engine): bool
    {
        return (bool) $engine->delete();
    }
}
