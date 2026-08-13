<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface UpsertVehicleFromSheetServiceInterface
{
    /**
     * Создать или обновить автомобиль из Excel row.
     *
     * Шаги:
     * 1) Преобразовать sheet row DTO в VehicleData.
     * 2) Применить write policy к incoming/existing snapshot.
     * 3) Выполнить create/update и вернуть актуальный snapshot.
     */
    public function upsertFromRow(VehicleSheetRowDTO $row): VehicleData;
}
