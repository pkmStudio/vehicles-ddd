<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Commands;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Catalog\Domain\ModelData\ModificationData;
use App\Vehicles\Catalog\Infrastructure\Models\Modification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись модификаций через Eloquent-модель фичи Catalog.
 */
final readonly class ModificationCommand implements ModificationCommandInterface
{
    /**
     * Создает запись модификаций.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(ModificationData $data): ModificationData
    {
        return DB::transaction(
            fn (): ModificationData => ModificationData::from(
                Modification::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    /**
     * Обновляет запись модификаций.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(ModificationData $data): ModificationData
    {
        return DB::transaction(function () use ($data): ModificationData {
            $modification = Modification::query()
                ->where('mod_id', $data->modId)
                ->where('type', $data->type->value)
                ->firstOrFail();
            $modification->fill(Arr::except($data->toArray(), ['id']));
            $modification->save();

            return ModificationData::from($modification->refresh());
        });
    }

    /**
     * Удаляет запись модификаций по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    public function deleteByModIdAndType(int $modId, string $type): void
    {
        DB::transaction(function () use ($modId, $type): void {
            Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->delete();
        });
    }
}
