<?php

declare(strict_types=1);

namespace App\Vehicles\Commands;

use App\Vehicles\Commands\Contracts\EngineCommandInterface;
use App\Vehicles\Models\Engine;

final class EngineCommand implements EngineCommandInterface
{
    public function create(array $attributes): Engine
    {
        return Engine::query()->create($attributes);
    }

    public function update(Engine $engine, array $attributes): Engine
    {
        $engine->update($attributes);

        return $engine;
    }

    public function updateOrCreate(array $attributes, array $values = []): Engine
    {
        return Engine::query()->updateOrCreate($attributes, $values);
    }

    public function delete(Engine $engine): bool
    {
        return (bool) $engine->delete();
    }
}
