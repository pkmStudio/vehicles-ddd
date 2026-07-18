<?php

use App\Providers\AuthServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Modules\Templates\Infrastructure\Providers\TemplatesServiceProvider;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Modules\Vehicles\Features\Export\Infrastructure\Providers\ExportServiceProvider;
use App\Modules\Vehicles\Features\Import\Infrastructure\Providers\ImportEventServiceProvider;
use App\Modules\Vehicles\Features\Import\Infrastructure\Providers\ImportServiceProvider;
use App\Modules\Vehicles\Features\Maintenance\Infrastructure\Providers\MaintenanceServiceProvider;
use App\Modules\Vehicles\Shared\Infrastructure\Providers\VehiclesServiceProvider;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Providers\CatalogServiceProvider as WarehouseCatalogServiceProvider;
use App\Modules\Warehouse\Features\Export\Infrastructure\Providers\ExportServiceProvider as WarehouseExportServiceProvider;
use App\Modules\Warehouse\Features\Import\Infrastructure\Providers\ImportEventServiceProvider as WarehouseImportEventServiceProvider;
use App\Modules\Warehouse\Features\Import\Infrastructure\Providers\ImportServiceProvider as WarehouseImportServiceProvider;
use App\Modules\Warehouse\Features\KitProperties\Infrastructure\Providers\KitPropertiesServiceProvider;
use App\Modules\Warehouse\Features\Maintenance\Infrastructure\Providers\MaintenanceServiceProvider as WarehouseMaintenanceServiceProvider;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Providers\MoySkladServiceProvider as WarehouseMoySkladServiceProvider;
use App\Modules\Warehouse\Features\Packaging\Infrastructure\Providers\PackagingServiceProvider;
use App\Modules\Warehouse\Shared\Infrastructure\Providers\WarehouseServiceProvider;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Infrastructure\Providers\WiperAdapterAuditServiceProvider;

return [
    AuthServiceProvider::class,
    HorizonServiceProvider::class,
    VehiclesServiceProvider::class,
    WarehouseServiceProvider::class,
    WarehouseCatalogServiceProvider::class,
    TemplatesServiceProvider::class,
    ImportServiceProvider::class,
    ImportEventServiceProvider::class,
    MaintenanceServiceProvider::class,
    ExportServiceProvider::class,
    WarehouseExportServiceProvider::class,
    WiperAdapterAuditServiceProvider::class,
    WarehouseImportServiceProvider::class,
    WarehouseImportEventServiceProvider::class,
    WarehouseMaintenanceServiceProvider::class,
    WarehouseMoySkladServiceProvider::class,
    PackagingServiceProvider::class,
    KitPropertiesServiceProvider::class,
    CatalogServiceProvider::class,
];
