<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Commands;

use App\Vehicles\Catalog\Domain\ModelData\VehicleData;

interface VehicleCommandInterface
{
    public function create(VehicleData $data): VehicleData;

    public function update(VehicleData $data): VehicleData;

    public function deleteByMsId(int $msId): void;
}
