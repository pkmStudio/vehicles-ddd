<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ManufacturerCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись производителей через Eloquent-модель фичи Catalog.
 */
final readonly class ManufacturerCommand implements ManufacturerCommandInterface
{
    public function __construct(
        private VehicleCommandInterface $vehicles,
    ) {}

    /**
     * Создает запись производителей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(ManufacturerData $data): ManufacturerData
    {
        return DB::transaction(
            fn (): ManufacturerData => ManufacturerData::from(
                Manufacturer::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    /**
     * Обновляет запись производителей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(ManufacturerData $data): ManufacturerData
    {
        return DB::transaction(function () use ($data): ManufacturerData {
            $manufacturer = Manufacturer::query()->where('mfa_id', $data->mfaId)->firstOrFail();
            $manufacturer->fill(Arr::except($data->toArray(), ['id']));
            $manufacturer->save();

            return ManufacturerData::from($manufacturer->refresh());
        });
    }

    /**
     * Удаляет запись производителей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись и зависимые записи внутри транзакции.
     */
    public function deleteByMfaId(int $mfaId): void
    {
        DB::transaction(function () use ($mfaId): void {
            $manufacturer = Manufacturer::query()->where('mfa_id', $mfaId)->first();
            if ($manufacturer === null) {
                return;
            }

            $vehicleIds = Vehicle::query()
                ->where('manufacturer_id', $manufacturer->id)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $this->vehicles->deleteByIds($vehicleIds);
            $manufacturer->delete();
        });
    }
}
