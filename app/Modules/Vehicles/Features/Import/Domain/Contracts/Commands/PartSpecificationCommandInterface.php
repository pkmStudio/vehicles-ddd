<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;

interface PartSpecificationCommandInterface
{
    /**
     * Создать part specification из import data.
     *
     * Шаги:
     * 1) Передать validated PartSpecificationData в write adapter.
     * 2) Вернуть snapshot созданной записи.
     */
    public function create(PartSpecificationData $data): PartSpecificationData;

    /**
     * Обновляет запись, найденную по $data->id.
     *
     * Шаги:
     * 1) Найти specification по локальному id из PartSpecificationData.
     * 2) Применить новые значения import data.
     * 3) Вернуть обновленный snapshot.
     */
    public function update(PartSpecificationData $data): PartSpecificationData;
}
