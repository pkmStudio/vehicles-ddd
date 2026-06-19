<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Commands;

use App\Vehicles\Domain\ModelData\Modification\ModificationData;
use App\Vehicles\Domain\Models\Modification;

interface ModificationCommandInterface
{
    public function create(ModificationData $data): Modification;

    public function update(Modification $modification, ModificationData $data): Modification;

    /** Upsert по составному натуральному ключу (mod_id + type). */
    public function upsertByModIdAndType(ModificationData $data): Modification;

    public function delete(Modification $modification): bool;
}
