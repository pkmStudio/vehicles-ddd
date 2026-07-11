<?php

return [
    App\Providers\AuthServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Vehicles\Templates\Application\TemplatesServiceProvider::class,
    App\Vehicles\Import\Infrastructure\Providers\ImportServiceProvider::class,
    App\Vehicles\Import\Infrastructure\Providers\ImportEventServiceProvider::class,
    App\Vehicles\Export\Infrastructure\Providers\ExportServiceProvider::class,
];
