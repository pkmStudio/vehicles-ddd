<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Commands;

use App\Warehouse\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Warehouse\Import\Domain\ModelData\KitData;
use App\Warehouse\Import\Infrastructure\Models\Kit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Пишет Warehouse-набор и его состав через Eloquent-копию модели Import-фичи.
 */
final readonly class KitCommand implements KitCommandInterface
{
    /**
     * Этот метод находит/создаёт набор и полностью переattach'ивает его состав.
     *
     * Шаги:
     * 1) Найти запись по $kitId, если он передан, иначе по import_hash (дедупликация повторного
     *    импорта того же состава).
     * 2) Обновить найденную запись либо создать новую.
     * 3) Отвязать весь текущий состав и привязать заново в переданном порядке (sort = позиция).
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    public function upsert(KitData $data, ?int $kitId, array $nomenclatureIds): KitData
    {
        return DB::transaction(function () use ($data, $kitId, $nomenclatureIds): KitData {
            $kit = $kitId !== null ? Kit::query()->find($kitId) : null;

            if ($kit === null && $data->importHash !== null) {
                $kit = Kit::query()->where('import_hash', $data->importHash)->first();
            }

            $values = Arr::except($data->toArray(), ['id']);
            $kit = $kit !== null ? tap($kit)->update($values) : Kit::query()->create($values);

            $kit->nomenclatures()->detach();

            $pivot = [];
            foreach ($nomenclatureIds as $sort => $nomenclatureId) {
                $pivot[] = [
                    'kit_id' => $kit->id,
                    'nomenclature_id' => $nomenclatureId,
                    'sort' => $sort,
                    'updated_at' => now(),
                ];
            }
            $kit->nomenclatures()->attach($pivot);

            return KitData::from($kit->refresh());
        });
    }
}
