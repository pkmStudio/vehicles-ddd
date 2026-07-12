<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Commands;

use App\Vehicles\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Vehicles\Catalog\Domain\ModelData\VehicleData;
use App\Vehicles\Catalog\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись автомобилей через Eloquent-модель фичи Catalog.
 */
final readonly class VehicleCommand implements VehicleCommandInterface
{
    /**
     * Создает запись автомобилей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(VehicleData $data): VehicleData
    {
        return DB::transaction(
            fn (): VehicleData => VehicleData::from(
                Vehicle::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    /**
     * Обновляет запись автомобилей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(VehicleData $data): VehicleData
    {
        return DB::transaction(function () use ($data): VehicleData {
            $vehicle = Vehicle::query()->where('ms_id', $data->msId)->firstOrFail();
            $vehicle->fill(Arr::except($data->toArray(), ['id']));
            $vehicle->save();

            return VehicleData::from($vehicle->refresh());
        });
    }

    /**
     * Удаляет запись автомобилей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    public function deleteByMsId(int $msId): void
    {
        DB::transaction(function () use ($msId): void {
            Vehicle::query()->where('ms_id', $msId)->delete();
        });
    }
}
