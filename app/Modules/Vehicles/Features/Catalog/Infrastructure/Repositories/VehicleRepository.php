<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;

/**
 * Читает автомобилей через Eloquent-модель фичи Catalog.
 */
final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок автомобилей по внешнему идентификатору.
     */
    public function findByMsId(int $msId): ?VehicleData
    {
        return VehicleData::optional(Vehicle::query()->where('ms_id', $msId)->first());
    }

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function vehicleIdByMsId(int $msId): ?int
    {
        $id = Vehicle::query()->where('ms_id', $msId)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Возвращает внутренний id записи по внешнему идентификатору.
     */
    public function manufacturerIdByMfaId(int $mfaId): ?int
    {
        $id = Manufacturer::query()->where('mfa_id', $mfaId)->value('id');

        return $id === null ? null : (int) $id;
    }

}
