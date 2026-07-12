<?php

return [
    App\Providers\AuthServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Vehicles\Infrastructure\Providers\VehiclesServiceProvider::class,
    App\Warehouse\Infrastructure\Providers\WarehouseServiceProvider::class,
    App\Templates\Infrastructure\Providers\TemplatesServiceProvider::class,
    App\Vehicles\Import\Infrastructure\Providers\ImportServiceProvider::class,
    App\Vehicles\Import\Infrastructure\Providers\ImportEventServiceProvider::class,
    App\Vehicles\Export\Infrastructure\Providers\ExportServiceProvider::class,
    App\Vehicles\Catalog\Infrastructure\Providers\CatalogServiceProvider::class,
];
