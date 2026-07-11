<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Commands;

use App\Vehicles\Import\Domain\ModelData\VehicleData;

interface VehicleCommandInterface
{
    /** Upsert по натуральному ключу ms_id. */
    public function upsertByMsId(VehicleData $data): VehicleData;
}
