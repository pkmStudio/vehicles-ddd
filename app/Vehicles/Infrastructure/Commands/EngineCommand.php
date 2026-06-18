<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Application\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Application\ModelData\Engine\EngineData;
use App\Vehicles\Domain\Models\Engine;

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
