<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Contracts;

use App\Vehicles\DTOs\EngineData;
use App\Vehicles\Models\Engine;

interface EngineCommandInterface
{
    public function create(EngineData $data): Engine;

    public function update(Engine $engine, EngineData $data): Engine;

    /** Upsert по натуральному ключу eng_id. */
    public function upsertByEngId(EngineData $data): Engine;

    public function delete(Engine $engine): bool;
}
