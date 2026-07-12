<?php

use App\Providers\AuthServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Templates\Infrastructure\Providers\TemplatesServiceProvider;
use App\Vehicles\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Vehicles\Export\Infrastructure\Providers\ExportServiceProvider;
use App\Vehicles\Import\Infrastructure\Providers\ImportEventServiceProvider;
use App\Vehicles\Import\Infrastructure\Providers\ImportServiceProvider;
use App\Vehicles\Infrastructure\Providers\VehiclesServiceProvider;
use App\Warehouse\Export\Infrastructure\Providers\ExportServiceProvider as WarehouseExportServiceProvider;
use App\Warehouse\Infrastructure\Providers\WarehouseServiceProvider;

return [
    AuthServiceProvider::class,
    HorizonServiceProvider::class,
    VehiclesServiceProvider::class,
    WarehouseServiceProvider::class,
    TemplatesServiceProvider::class,
    ImportServiceProvider::class,
    ImportEventServiceProvider::class,
    ExportServiceProvider::class,
    WarehouseExportServiceProvider::class,
    CatalogServiceProvider::class,
];
