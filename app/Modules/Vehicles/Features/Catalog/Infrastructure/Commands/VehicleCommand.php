<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\VehicleCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use App\Modules\Vehicles\Shared\Domain\Enums\PartableTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись автомобилей через Eloquent-модель фичи Catalog.
 */
final readonly class VehicleCommand implements VehicleCommandInterface
{
    public function __construct(
        private ModificationCommandInterface $modifications,
        private PartSpecificationCommandInterface $partSpecifications,
    ) {}

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
     * 2) Удалить запись и зависимые записи внутри транзакции.
     */
    public function deleteByMsId(int $msId): void
    {
        DB::transaction(function () use ($msId): void {
            $vehicle = Vehicle::query()->where('ms_id', $msId)->first();
            if ($vehicle === null) {
                return;
            }

            $this->deleteByIds([(int) $vehicle->id]);
        });
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            $childIds = Vehicle::query()
                ->whereIn('parent_id', $ids)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
            $childIds = array_values(array_diff($childIds, $ids));

            $modificationIds = Modification::query()
                ->whereIn('vehicle_id', $ids)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $partSpecificationIds = PartSpecification::query()
                ->where('partable_type', PartableTypeEnum::VEHICLE->value)
                ->whereIn('partable_id', $ids)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $this->deleteByIds($childIds);
            $this->modifications->deleteByIds($modificationIds);
            $this->partSpecifications->deleteByIds($partSpecificationIds);

            Vehicle::query()->whereIn('id', $ids)->delete();
        });
    }
}
