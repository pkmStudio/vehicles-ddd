<?php

use App\Providers\AuthServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Templates\Infrastructure\Providers\TemplatesServiceProvider;
use App\Vehicles\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Vehicles\Export\Infrastructure\Providers\ExportServiceProvider;
use App\Vehicles\Import\Infrastructure\Providers\ImportEventServiceProvider;
use App\Vehicles\Import\Infrastructure\Providers\ImportServiceProvider;
use App\Vehicles\Infrastructure\Providers\VehiclesServiceProvider;
use App\Warehouse\Catalog\Infrastructure\Providers\CatalogServiceProvider as WarehouseCatalogServiceProvider;
use App\Warehouse\Export\Infrastructure\Providers\ExportServiceProvider as WarehouseExportServiceProvider;
use App\Warehouse\Import\Infrastructure\Providers\ImportEventServiceProvider as WarehouseImportEventServiceProvider;
use App\Warehouse\Import\Infrastructure\Providers\ImportServiceProvider as WarehouseImportServiceProvider;
use App\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;
use App\Warehouse\KitProperties\Infrastructure\Providers\KitPropertiesServiceProvider;
use App\Warehouse\Packaging\Infrastructure\Providers\PackagingServiceProvider;
use App\Warehouse\WiperAdapterAudit\Infrastructure\Providers\WiperAdapterAuditServiceProvider;

return [
    AuthServiceProvider::class,
    HorizonServiceProvider::class,
    VehiclesServiceProvider::class,
    WarehouseServiceProvider::class,
    WarehouseCatalogServiceProvider::class,
    TemplatesServiceProvider::class,
    ImportServiceProvider::class,
    ImportEventServiceProvider::class,
    ExportServiceProvider::class,
    WarehouseExportServiceProvider::class,
    WiperAdapterAuditServiceProvider::class,
    WarehouseImportServiceProvider::class,
    WarehouseImportEventServiceProvider::class,
    PackagingServiceProvider::class,
    KitPropertiesServiceProvider::class,
    CatalogServiceProvider::class,
];
