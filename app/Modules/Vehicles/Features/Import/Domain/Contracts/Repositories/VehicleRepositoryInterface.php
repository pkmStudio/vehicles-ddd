<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface VehicleRepositoryInterface
{
    /**
     * Найти автомобиль по внешнему ms_id.
     *
     * Шаги:
     * 1) Выполнить read query по ms_id.
     * 2) Вернуть VehicleData или null.
     */
    public function findByMsId(int $msId): ?VehicleData;

    /**
     * ТС с минимальным ms_id (для генерации отрицательных id новых ТС).
     *
     * Шаги:
     * 1) Отсортировать автомобили по ms_id.
     * 2) Вернуть snapshot минимальной записи или null.
     */
    public function findMinMsId(): ?VehicleData;
}
