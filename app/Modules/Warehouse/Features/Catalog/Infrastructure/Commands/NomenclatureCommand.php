<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись Warehouse-номенклатуры через Eloquent-модель Catalog-фичи.
 */
final readonly class NomenclatureCommand implements NomenclatureCommandInterface
{
    /**
     * Создаёт номенклатуру внутри транзакции.
     */
    public function create(NomenclatureData $data): NomenclatureData
    {
        return DB::transaction(
            fn (): NomenclatureData => NomenclatureData::from(
                Nomenclature::query()->create(Arr::except($data->toArray(), ['id', 'type', 'brand'])),
            ),
        );
    }

    /**
     * Обновляет номенклатуру внутри транзакции.
     */
    public function update(NomenclatureData $data): NomenclatureData
    {
        return DB::transaction(function () use ($data): NomenclatureData {
            $nomenclature = Nomenclature::query()->findOrFail($data->id);
            $nomenclature->fill(Arr::except($data->toArray(), ['id', 'type', 'brand']));
            $nomenclature->save();

            return NomenclatureData::from($nomenclature->refresh());
        });
    }

    /**
     * Удаляет номенклатуру внутри транзакции.
     */
    public function deleteById(int $id): void
    {
        $this->deleteByIds([$id]);
    }

    /**
     * Удаляет номенклатуру и её связи внутри транзакции.
     *
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($ids): void {
            DB::table('kit_nomenclature')->whereIn('nomenclature_id', $ids)->delete();
            DB::table('nomenclature_integrations')
                ->whereIn('nomenclature_id', $ids)
                ->update([
                    'nomenclature_id' => null,
                    'updated_at' => now(),
                ]);

            Nomenclature::query()->whereIn('id', $ids)->delete();
        });
    }
}
