<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Repositories;

use App\Vehicles\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;
use App\Vehicles\Catalog\Infrastructure\Models\Manufacturer;
use App\Vehicles\Catalog\Infrastructure\Models\Vehicle;

final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function firstByMfaId(int $mfaId): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->where('mfa_id', $mfaId)->first());
    }

    public function vehicleCountByMfaId(int $mfaId): ?int
    {
        $manufacturer = Manufacturer::query()->where('mfa_id', $mfaId)->first();
        if ($manufacturer === null) {
            return null;
        }

        return Vehicle::query()->where('manufacturer_id', $manufacturer->id)->count();
    }
}
