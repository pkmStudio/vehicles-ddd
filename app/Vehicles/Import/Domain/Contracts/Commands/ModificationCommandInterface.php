<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\ModificationData;

interface ModificationCommandInterface
{
    public function create(ModificationData $data): ModificationData;

    /** Обновляет запись, найденную по $data->id. */
    public function update(ModificationData $data): ModificationData;

    /** Upsert по составному натуральному ключу (mod_id + type). */
    public function upsertByModIdAndType(ModificationData $data): ModificationData;

    public function delete(ModificationData $data): bool;
}
