<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

/**
 * Запись Manufacturer (write). Принимает и отдаёт ManufacturerData — Eloquent-модель наружу
 * не выходит (деталь Infrastructure, см. plan.md §3).
 */
interface ManufacturerCommandInterface
{
    /**
     * Создать производителя из import data.
     *
     * Шаги:
     * 1) Передать validated ManufacturerData в write adapter.
     * 2) Вернуть snapshot созданной записи.
     */
    public function create(ManufacturerData $data): ManufacturerData;

    /**
     * Обновить существующего производителя.
     *
     * Шаги:
     * 1) Принять ManufacturerData после проверки существования записи.
     * 2) Применить значения ManufacturerData.
     * 3) Вернуть обновленный snapshot.
     */
    public function update(ManufacturerData $data): ManufacturerData;
}
