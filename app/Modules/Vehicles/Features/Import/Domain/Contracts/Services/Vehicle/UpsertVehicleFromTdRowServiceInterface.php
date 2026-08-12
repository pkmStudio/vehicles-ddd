<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface UpsertVehicleFromTdRowServiceInterface
{
    /**
     * Создать или обновить автомобиль из TecDoc command row.
     *
     * Шаги:
     * 1) Преобразовать TD row DTO в VehicleData.
     * 2) Применить write policy к incoming/existing snapshot.
     * 3) Выполнить create/update или вернуть null при пропуске строки.
     */
    public function upsertFromRow(VehicleTdRowDTO $row): ?VehicleData;
}
