<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Kit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись Warehouse-наборов и их состава через Eloquent-модель Catalog-фичи.
 */
final readonly class KitCommand implements KitCommandInterface
{
    /**
     * Создаёт набор и полностью записывает pivot-состав внутри транзакции.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    public function create(KitData $data, array $nomenclatureIds): KitData
    {
        return DB::transaction(function () use ($data, $nomenclatureIds): KitData {
            $kit = Kit::query()->create(Arr::except($data->toArray(), ['id']));
            $this->syncNomenclatures((int) $kit->id, $nomenclatureIds);

            return KitData::from($kit->refresh());
        });
    }

    /**
     * Обновляет набор и полностью переписывает pivot-состав внутри транзакции.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    public function update(KitData $data, array $nomenclatureIds): KitData
    {
        return DB::transaction(function () use ($data, $nomenclatureIds): KitData {
            $kit = Kit::query()->findOrFail($data->id);
            $kit->fill(Arr::except($data->toArray(), ['id']));
            $kit->save();
            $this->syncNomenclatures((int) $kit->id, $nomenclatureIds);

            return KitData::from($kit->refresh());
        });
    }

    /**
     * Удаляет набор и вручную очищает pivot-состав внутри транзакции.
     */
    public function deleteById(int $id): void
    {
        $this->deleteByIds([$id]);
    }

    /**
     * Удаляет наборы и вручную очищает pivot-состав внутри транзакции.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            DB::table('kit_nomenclature')->whereIn('kit_id', $ids)->delete();
            Kit::query()->whereIn('id', $ids)->delete();
        });
    }

    /**
     * Полностью переписывает состав набора в порядке входящего массива id.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    private function syncNomenclatures(int $kitId, array $nomenclatureIds): void
    {
        DB::table('kit_nomenclature')->where('kit_id', $kitId)->delete();

        $now = now();
        $rows = [];
        foreach ($nomenclatureIds as $sort => $nomenclatureId) {
            $rows[] = [
                'kit_id' => $kitId,
                'nomenclature_id' => $nomenclatureId,
                'sort' => $sort,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('kit_nomenclature')->insert($rows);
        }
    }
}
