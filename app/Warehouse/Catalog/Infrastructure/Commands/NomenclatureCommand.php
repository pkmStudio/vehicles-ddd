<?php

declare(strict_types=1);

namespace App\Warehouse\Catalog\Infrastructure\Commands;

use App\Warehouse\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Catalog\Domain\ModelData\NomenclatureData;
use App\Warehouse\Catalog\Infrastructure\Models\Nomenclature;
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
     * Удаляет номенклатуру без каскада внутри транзакции.
     */
    public function deleteById(int $id): void
    {
        DB::transaction(function () use ($id): void {
            Nomenclature::query()->whereKey($id)->delete();
        });
    }
}
