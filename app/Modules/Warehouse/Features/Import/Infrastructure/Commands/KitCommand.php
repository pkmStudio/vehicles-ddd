<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportPersistenceException;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Kit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Пишет Warehouse-набор и его состав через Eloquent-копию модели Import-фичи.
 */
final readonly class KitCommand implements KitCommandInterface
{
    /**
     * Этот метод обновляет набор и полностью переattach'ивает его состав.
     *
     * Шаги:
     * 1) Открыть транзакцию записи набора и pivot-состава.
     * 2) Найти Kit по id из DTO и выбросить import exception, если он отсутствует.
     * 3) Обновить поля набора без id.
     * 4) Полностью заменить связи с номенклатурами в заданном порядке.
     * 5) Вернуть обновлённый KitData после refresh.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    public function updateById(KitData $data, array $nomenclatureIds): KitData
    {
        return DB::transaction(function () use ($data, $nomenclatureIds): KitData {
            $values = Arr::except($data->toArray(), ['id']);
            $kit = $data->id === null ? null : Kit::query()->find($data->id);

            if ($kit === null) {
                throw ImportPersistenceException::withMessage("Набор с ID {$data->id} не найден");
            }

            $kit->update($values);
            $this->attachNomenclatures($kit, $nomenclatureIds);

            return KitData::from($kit->refresh());
        });
    }

    /**
     * Этот метод создаёт набор и полностью attach'ит его состав.
     *
     * Шаги:
     * 1) Открыть транзакцию создания набора и pivot-состава.
     * 2) Создать Kit из DTO без id.
     * 3) Привязать номенклатуры с sort по порядку строки.
     * 4) Вернуть созданный KitData после refresh.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    public function create(KitData $data, array $nomenclatureIds): KitData
    {
        return DB::transaction(function () use ($data, $nomenclatureIds): KitData {
            $values = Arr::except($data->toArray(), ['id']);
            $kit = Kit::query()->create($values);
            $this->attachNomenclatures($kit, $nomenclatureIds);

            return KitData::from($kit->refresh());
        });
    }

    /**
     * Полностью заменяет состав набора.
     *
     * @param  array<int, int>  $nomenclatureIds
     */
    private function attachNomenclatures(Kit $kit, array $nomenclatureIds): void
    {
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
    }
}
