<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Factories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Factories\ManufacturerDataFactoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerTdRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;

/**
 * Собирает ManufacturerData из уже валидированных DTO строк импорта.
 */
final readonly class ManufacturerDataFactory implements ManufacturerDataFactoryInterface
{
    /**
     * Собирает typed `ManufacturerData` из внешней import-строки производителя.
     *
     * Шаги:
     * 1) Взять нормализованные identifier, название и provider из DTO.
     * 2) Передать значения в общий builder производителя.
     */
    public function makeFromSheetRow(ManufacturerSheetRowDTO $row): ManufacturerData
    {
        return new ManufacturerData(
            mfaId: $row->mfaId,
            name: $row->name,
            provider: $row->provider,
        );
    }

    /**
     * Собирает typed `ManufacturerData` из command DTO производителя.
     *
     * Шаги:
     * 1) Взять identifier и название из typed command DTO.
     * 2) Применить provider TD.
     * 3) Передать значения в общий builder производителя.
     */
    public function makeFromTdRow(ManufacturerTdRowDTO $row): ManufacturerData
    {
        return new ManufacturerData(
            mfaId: $row->mfaId,
            name: $row->name,
            provider: ProviderEnum::TD,
        );
    }

    /**
     * Собирает данные производителя из ручной строки vehicle-листа.
     *
     * Шаги:
     * 1) Принять назначенный `mfa_id`.
     * 2) Взять название производителя из typed vehicle row DTO.
     * 3) Применить provider OD.
     */
    public function makeFromVehicleSheetRow(VehicleSheetRowDTO $row, int $mfaId): ManufacturerData
    {
        return new ManufacturerData(
            mfaId: $mfaId,
            name: $row->manufacturerName,
            provider: ProviderEnum::OD,
        );
    }
}
