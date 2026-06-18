<?php

declare(strict_types=1);

namespace App\Vehicles\Commands\Modification;

use App\Vehicles\DTOs\Modification\ModificationData;
use App\Vehicles\Models\Modification;

interface ModificationCommandInterface
{
    public function create(ModificationData $data): Modification;

    public function update(Modification $modification, ModificationData $data): Modification;

    /** Upsert по составному натуральному ключу (mod_id + type). */
    public function upsertByModIdAndType(ModificationData $data): Modification;

    public function delete(Modification $modification): bool;
}
