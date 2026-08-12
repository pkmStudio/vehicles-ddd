<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;

/**
 * Читает vehicle snapshots для import-сценариев Vehicles.
 */
final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Ищет автомобиль по TecDoc `ms_id`.
     *
     * Шаги:
     * 1) Прочитать vehicle-модель с parent relation по `ms_id`.
     * 2) Если модель не найдена — вернуть null.
     * 3) Сконвертировать модель в `VehicleData` с дополнительным `parent_ms_id`.
     */
    public function findByMsId(int $msId): ?VehicleData
    {
        $vehicle = Vehicle::query()
            ->with('parent')
            ->where('ms_id', $msId)
            ->first();

        if ($vehicle === null) {
            return null;
        }

        return VehicleData::from([
            ...$vehicle->toArray(),
            'parent_ms_id' => $vehicle->parent?->ms_id,
        ]);
    }

    /**
     * Возвращает автомобиль с минимальным `ms_id`.
     *
     * Шаги:
     * 1) Отсортировать vehicles по `ms_id`.
     * 2) Взять первую запись.
     * 3) Сконвертировать найденную Eloquent-модель в optional `VehicleData`.
     */
    public function findMinMsId(): ?VehicleData
    {
        return VehicleData::optional(
            Vehicle::query()
                ->orderBy('ms_id')
                ->first(),
        );
    }
}
