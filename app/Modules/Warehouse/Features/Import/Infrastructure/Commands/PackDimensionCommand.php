<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Commands;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportPersistenceException;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\PackDimensionData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\PackDimension;
use Illuminate\Support\Arr;

/**
 * Пишет упаковочный размер Warehouse через Eloquent-копию модели Import-фичи.
 */
final readonly class PackDimensionCommand implements PackDimensionCommandInterface
{
    /**
     * Обновляет упаковочный размер из import data.
     *
     * Шаги:
     * 1) Подготовить значения DTO без id.
     * 2) Найти упаковочный размер по id.
     * 3) Выбросить import exception, если запись отсутствует.
     * 4) Обновить запись и вернуть refreshed PackDimensionData.
     */
    public function update(PackDimensionData $data): PackDimensionData
    {
        $values = Arr::except($data->toArray(), ['id']);
        $packDimension = $data->id === null ? null : PackDimension::query()->find($data->id);

        if ($packDimension === null) {
            throw ImportPersistenceException::withMessage("Упаковочный размер с ID {$data->id} не найден");
        }

        $packDimension->update($values);

        return PackDimensionData::from($packDimension->refresh());
    }

    /**
     * Создаёт новый упаковочный размер.
     *
     * Шаги:
     * 1) Подготовить значения DTO без id.
     * 2) Создать запись упаковочного размера.
     * 3) Вернуть PackDimensionData созданной записи.
     */
    public function create(PackDimensionData $data): PackDimensionData
    {
        $values = Arr::except($data->toArray(), ['id']);

        return PackDimensionData::from(PackDimension::query()->create($values));
    }
}
