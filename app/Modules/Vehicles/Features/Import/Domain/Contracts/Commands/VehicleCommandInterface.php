<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface VehicleCommandInterface
{
    public function create(VehicleData $data): VehicleData;

    public function updateByMsId(VehicleData $data): VehicleData;
}
