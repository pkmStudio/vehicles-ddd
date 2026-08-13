<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface ManufacturerDataFactoryInterface
{
    /**
     * Собрать ManufacturerData из внешней sheet-строки импорта.
     *
     * Шаги:
     * 1) Принять уже валидированную DTO строку.
     * 2) Нормализовать значения в ManufacturerData.
     */
    public function makeFromSheetRow(ManufacturerSheetRowDTO $row): ManufacturerData;

    /**
     * Собрать ManufacturerData из командной TecDoc-строки импорта.
     *
     * Шаги:
     * 1) Принять уже валидированную command DTO строку.
     * 2) Применить provider TD.
     * 3) Нормализовать значения в ManufacturerData.
     */
    public function makeFromCommandRow(ManufacturerCommandRowDTO $row): ManufacturerData;

    /**
     * Собрать ManufacturerData для производителя, созданного из строки ручного vehicle-листа.
     *
     * Шаги:
     * 1) Принять уже назначенный `mfa_id`.
     * 2) Взять имя производителя из typed vehicle row DTO.
     * 3) Применить provider OD.
     */
    public function makeFromVehicleSheetRow(VehicleSheetRowDTO $row, int $mfaId): ManufacturerData;
}
