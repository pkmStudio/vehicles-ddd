<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Commands;

use App\Warehouse\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Import\Domain\ModelData\NomenclatureData;
use App\Warehouse\Import\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Пишет Warehouse-номенклатуру через Eloquent-копию модели Import-фичи.
 */
final readonly class NomenclatureCommand implements NomenclatureCommandInterface
{
    /**
     * Обновляет запись по id. Бросает исключение, если запись не найдена или артикул уже занят
     * другой записью (уникальный индекс part_number допускает конфликт только с самой собой).
     */
    public function updateById(NomenclatureData $data): NomenclatureData
    {
        $nomenclature = Nomenclature::query()->find($data->id);

        if ($nomenclature === null) {
            throw new RuntimeException("Запись с ID {$data->id} не найдена");
        }

        $conflict = Nomenclature::query()
            ->where('part_number', $data->partNumber)
            ->where('id', '!=', $data->id)
            ->first();

        if ($conflict !== null) {
            throw new RuntimeException("Артикул '{$data->partNumber}' уже используется записью с ID {$conflict->id}");
        }

        $nomenclature->update(Arr::except($data->toArray(), ['id']));

        return NomenclatureData::from($nomenclature->refresh());
    }

    /**
     * Создаёт запись либо обновляет существующую по уникальному part_number.
     */
    public function upsertByPartNumber(NomenclatureData $data): NomenclatureData
    {
        $nomenclature = Nomenclature::query()->updateOrCreate(
            ['part_number' => $data->partNumber],
            Arr::except($data->toArray(), ['id']),
        );

        return NomenclatureData::from($nomenclature);
    }
}
