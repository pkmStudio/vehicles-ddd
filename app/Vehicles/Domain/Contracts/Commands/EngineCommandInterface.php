<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Commands;

use App\Vehicles\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Domain\Models\Engine;

interface EngineCommandInterface
{
    public function create(EngineData $data): Engine;

    public function update(Engine $engine, EngineData $data): Engine;

    /** Upsert по натуральному ключу eng_id. */
    public function upsertByEngId(EngineData $data): Engine;

    public function delete(Engine $engine): bool;
}
