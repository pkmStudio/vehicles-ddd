<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface ModificationCommandInterface
{
    /** Upsert по составному натуральному ключу (mod_id + type). */
    public function upsertByModIdAndType(ModificationData $data): ModificationData;
}
