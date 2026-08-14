<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowReferenceNotFoundException;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;

interface VehicleWiperSpecificationImportServiceInterface
{
    /**
     * Импортировать specification дворников из Excel row.
     *
     * Шаги:
     * 1) Найти автомобиль по ключу строки.
     * 2) Разделить/нормализовать wiper details по сторонам.
     * 3) Создать или обновить part specifications.
     *
     * @throws ImportRowValidationException
     * @throws ImportRowReferenceNotFoundException
     */
    public function upsertFromRow(VehicleWiperSheetRowDTO $row): void;
}
