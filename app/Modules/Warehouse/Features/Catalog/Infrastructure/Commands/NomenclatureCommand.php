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
     *
     * Шаги:
     * 1) Исключить технический id из входного Data.
     * 2) Создать Eloquent-модель каталога внутри транзакции.
     * 3) Вернуть обновлённый Data-снимок созданной записи.
     */
    public function create(NomenclatureData $data): NomenclatureData
    {
        $createNomenclature = fn (): NomenclatureData => NomenclatureData::from(
            Nomenclature::query()->create(Arr::except($data->toArray(), ['id', 'type', 'brand'])),
        );

        return DB::transaction($createNomenclature);
    }

    /**
     * Обновляет номенклатуру внутри транзакции.
     *
     * Шаги:
     * 1) Найти Eloquent-модель по id из Data.
     * 2) Заполнить изменяемые поля и сохранить запись в транзакции.
     * 3) Вернуть Data-снимок обновлённой модели.
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
     *
     * Шаги:
     * 1) Принять идентификатор или список идентификаторов каталога.
     * 2) Выполнить удаление Eloquent-записей внутри транзакции.
     * 3) Завершить без возврата бизнес-данных.
     */
    public function deleteById(int $id): void
    {
        $this->deleteByIds([$id]);
    }

    /**
     * Удаляет номенклатуру и её связи внутри транзакции.
     *
     * @param  array<int, int>  $ids
     *
     * Шаги:
     * 1) Принять идентификатор или список идентификаторов каталога.
     * 2) Выполнить удаление Eloquent-записей внутри транзакции.
     * 3) Завершить без возврата бизнес-данных.
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
