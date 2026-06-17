<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Contracts;

use App\Vehicles\Models\Engine;

/**
 * Запись Engine (write).
 */
interface EngineCommandInterface
{
    public function create(array $attributes): Engine;

    public function update(Engine $engine, array $attributes): Engine;

    public function updateOrCreate(array $attributes, array $values = []): Engine;

    public function delete(Engine $engine): bool;
}
