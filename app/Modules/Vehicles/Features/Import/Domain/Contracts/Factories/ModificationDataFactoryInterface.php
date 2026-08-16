<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification\ModificationTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;

interface ModificationDataFactoryInterface
{
    /**
     * Собрать ModificationData из command-строки импорта.
     *
     * Шаги:
     * 1) Провалидировать modification-поля строки.
     * 2) Нормализовать значения в ModificationData.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromTdRow(ModificationTdRowDTO $row): ModificationData;

    /**
     * Собрать ModificationData из manager sheet-строки импорта.
     *
     * Шаги:
     * 1) Провалидировать modification-поля typed DTO.
     * 2) Применить resolved mod_id/type/vehicle_id.
     * 3) Нормализовать значения в ModificationData.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromManagerSheetRow(ModificationSheetRowDTO $row, int $modId, string $type, int $vehicleId): ModificationData;
}
