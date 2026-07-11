<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\EngineData;

interface EngineCommandInterface
{
    public function create(EngineData $data): EngineData;

    /** Обновляет запись, найденную по $data->id. */
    public function update(EngineData $data): EngineData;

    /** Upsert по натуральному ключу eng_id. */
    public function upsertByEngId(EngineData $data): EngineData;

    /** Проставить группу двигателю (по $engine->id). */
    public function setGroupId(EngineData $engine, int $groupId): void;

    public function delete(EngineData $data): bool;
}
