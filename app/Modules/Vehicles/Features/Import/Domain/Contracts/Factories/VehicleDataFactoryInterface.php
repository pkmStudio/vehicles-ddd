<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\VehicleData;

interface VehicleDataFactoryInterface
{
    /**
     * Собрать VehicleData из строки ручного vehicle-листа.
     *
     * Шаги:
     * 1) Провалидировать vehicle-поля строки.
     * 2) Нормализовать значения в VehicleData.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromSheetRow(VehicleSheetRowDTO $row, int $msId, int $mfaId, int $manufacturerId, ?int $parentId): VehicleData;

    /**
     * Собрать VehicleData из строки TecDoc command import.
     *
     * Шаги:
     * 1) Провалидировать vehicle-поля typed command DTO.
     * 2) Привязать найденный manufacturer id.
     * 3) Нормализовать значения в VehicleData.
     *
     * @throws ImportRowValidationException
     */
    public function makeFromTdRow(VehicleTdRowDTO $row, int $manufacturerId): VehicleData;
}
