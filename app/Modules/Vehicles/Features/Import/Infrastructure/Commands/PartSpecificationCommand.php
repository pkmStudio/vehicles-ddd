<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\PartSpecification;
use Illuminate\Support\Arr;

final readonly class PartSpecificationCommand implements PartSpecificationCommandInterface
{
    private const array NON_WRITABLE_FIELDS = ['id'];

    /**
     * Создать part specification row через Eloquent.
     *
     * Шаги:
     * 1) Преобразовать PartSpecificationData в массив writable fields.
     * 2) Исключить локальный id.
     * 3) Создать запись и вернуть PartSpecificationData snapshot.
     */
    public function create(PartSpecificationData $data): PartSpecificationData
    {
        return PartSpecificationData::from(
            PartSpecification::query()->create(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS)),
        );
    }

    /**
     * Обновить part specification row по локальному id.
     *
     * Шаги:
     * 1) Найти specification по id из PartSpecificationData.
     * 2) Обновить writable fields из PartSpecificationData.
     * 3) Вернуть PartSpecificationData snapshot обновленной записи.
     */
    public function update(PartSpecificationData $data): PartSpecificationData
    {
        $specification = PartSpecification::query()->findOrFail($data->id);
        $specification->update(Arr::except($data->toArray(), self::NON_WRITABLE_FIELDS));

        return PartSpecificationData::from($specification);
    }
}
