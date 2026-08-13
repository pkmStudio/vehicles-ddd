<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;

final readonly class VehicleCommand implements VehicleCommandInterface
{
    private const array NON_WRITABLE_FIELDS = ['id', 'parent_ms_id'];

    /**
     * Создать vehicle row через Eloquent.
     *
     * Шаги:
     * 1) Преобразовать VehicleData в массив writable fields.
     * 2) Исключить служебные fields id/parent_ms_id.
     * 3) Создать запись и вернуть VehicleData snapshot.
     */
    public function create(VehicleData $data): VehicleData
    {
        return VehicleData::from(
            Vehicle::query()->create(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS)),
        );
    }

    /**
     * Обновить vehicle row через Eloquent.
     *
     * Шаги:
     * 1) Найти vehicle по внешнему ms_id из VehicleData.
     * 2) Обновить writable fields из VehicleData.
     * 3) Refresh model и вернуть VehicleData snapshot.
     */
    public function update(VehicleData $data): VehicleData
    {
        $vehicle = Vehicle::query()->where('ms_id', $data->msId)->firstOrFail();
        $vehicle->update(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS));

        return VehicleData::from($vehicle->refresh());
    }
}
