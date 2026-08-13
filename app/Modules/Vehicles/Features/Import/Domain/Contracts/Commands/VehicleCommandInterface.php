<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface VehicleCommandInterface
{
    /**
     * Создать автомобиль из import data.
     *
     * Шаги:
     * 1) Передать validated VehicleData в write adapter.
     * 2) Вернуть snapshot созданной записи.
     */
    public function create(VehicleData $data): VehicleData;

    /**
     * Обновить автомобиль по внешнему ms_id.
     *
     * Шаги:
     * 1) Найти существующую запись по ms_id.
     * 2) Применить значения VehicleData.
     * 3) Вернуть обновленный snapshot.
     */
    public function updateByMsId(VehicleData $data): VehicleData;
}
