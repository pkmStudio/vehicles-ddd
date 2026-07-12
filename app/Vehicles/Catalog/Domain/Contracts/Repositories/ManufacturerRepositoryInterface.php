<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Contracts\Repositories;

use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;

interface ManufacturerRepositoryInterface
{
    public function firstByMfaId(int $mfaId): ?ManufacturerData;

    public function vehicleCountByMfaId(int $mfaId): ?int;
}
