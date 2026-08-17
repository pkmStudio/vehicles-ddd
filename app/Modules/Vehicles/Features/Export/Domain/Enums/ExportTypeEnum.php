<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Enums;

/**
 * Тип каталога, который внешний сервис просит выгрузить через RabbitMQ.
 */
enum ExportTypeEnum: string
{
    case Vehicle = 'vehicle_multi_sheet';
    case Engine = 'engine_multi_sheet';
    case Manufacturer = 'manufacturer';
    case ModificationCatalog = 'modification_catalog';
    case EngineModifications = 'engine_modifications';
    case VehicleFullCsv = 'vehicle_full_csv';
    case VehicleCountryCsv = 'vehicle_country_csv';

    /**
     * Возвращает stable prefix имени export-файла для типа каталога.
     */
    public function filePrefix(): string
    {
        return match ($this) {
            self::Vehicle => 'vehicle-catalog',
            self::Engine => 'engine-catalog',
            self::Manufacturer => 'manufacturer-catalog',
            self::ModificationCatalog => 'modification-catalog',
            self::EngineModifications => 'engine-modifications',
            self::VehicleFullCsv => 'vehicle-full',
            self::VehicleCountryCsv => 'vehicle-country',
        };
    }
}
