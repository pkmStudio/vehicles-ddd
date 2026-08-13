<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;

interface VehicleWiperSpecificationImportServiceInterface
{
    /**
     * Импортировать specification дворников из Excel row.
     *
     * Шаги:
     * 1) Найти автомобиль по ключу строки.
     * 2) Разделить/нормализовать wiper details по сторонам.
     * 3) Создать или обновить part specifications.
     */
    public function upsertFromRow(VehicleWiperSheetRowDTO $row): void;
}
