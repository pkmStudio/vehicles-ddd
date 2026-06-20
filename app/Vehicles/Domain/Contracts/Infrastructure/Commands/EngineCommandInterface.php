<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Infrastructure\Commands;

use App\Vehicles\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Domain\Models\Engine;

interface EngineCommandInterface
{
    public function create(EngineData $data): Engine;

    public function update(Engine $engine, EngineData $data): Engine;

    /** Upsert по натуральному ключу eng_id. */
    public function upsertByEngId(EngineData $data): Engine;

    /** Частичное обновление редактируемых колонок по eng_id (создаёт, если нет). */
    public function updateEditableByEngId(int $engId, array $attributes): Engine;

    /** Проставить группу двигателю. */
    public function setGroupId(Engine $engine, int $groupId): void;

    public function delete(Engine $engine): bool;
}
