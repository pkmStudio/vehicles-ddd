<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Repositories;

use App\Vehicles\Catalog\Domain\DTOs\Vehicle\VehicleDeletionBlockersDTO;
use App\Vehicles\Catalog\Domain\ModelData\VehicleData;

interface VehicleRepositoryInterface
{
    public function firstByMsId(int $msId): ?VehicleData;

    public function vehicleIdByMsId(int $msId): ?int;

    public function manufacturerIdByMfaId(int $mfaId): ?int;

    public function deletionBlockersByMsId(int $msId): ?VehicleDeletionBlockersDTO;
}
