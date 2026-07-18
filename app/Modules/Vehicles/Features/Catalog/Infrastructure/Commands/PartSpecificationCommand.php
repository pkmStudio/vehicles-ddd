<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\PartSpecification;
use Illuminate\Support\Facades\DB;

/**
 * Выполняет запись спецификаций деталей через Eloquent-модель фичи Catalog.
 */
final readonly class PartSpecificationCommand implements PartSpecificationCommandInterface
{
    /**
     * Создает запись спецификаций деталей.
     *
     * Шаги:
     * 1) Выполнить запись внутри транзакции.
     * 2) Вернуть актуальный Data-снимок созданной записи.
     */
    public function create(PartSpecificationData $data): PartSpecificationData
    {
        return DB::transaction(
            fn (): PartSpecificationData => PartSpecificationData::from(
                PartSpecification::query()->create($data->toArray()),
            ),
        );
    }

    /**
     * Обновляет запись спецификаций деталей.
     *
     * Шаги:
     * 1) Найти существующую запись внутри транзакции.
     * 2) Применить новые значения и сохранить модель.
     * 3) Вернуть актуальный Data-снимок записи.
     */
    public function update(PartSpecificationData $data): PartSpecificationData
    {
        return DB::transaction(function () use ($data): PartSpecificationData {
            $specification = PartSpecification::query()->findOrFail($data->id);
            $specification->fill($data->toArray());
            $specification->save();

            return PartSpecificationData::from($specification->refresh());
        });
    }

    /**
     * Удаляет запись спецификаций деталей по внешнему идентификатору.
     *
     * Шаги:
     * 1) Найти целевую запись по идентификатору.
     * 2) Удалить запись внутри транзакции без каскада.
     */
    public function deleteById(int $id): void
    {
        DB::transaction(function () use ($id): void {
            PartSpecification::query()->where('id', $id)->delete();
        });
    }
}
