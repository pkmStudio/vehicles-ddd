<?php

return [
    App\Providers\HorizonServiceProvider::class,
    // Временный общий "shared kernel" — Repository-порты над ещё не разделёнными по фичам
    // Domain\Models. Исчезнет вместе с переездом на spatie/laravel-data (plan.md §3).
    App\Vehicles\Infrastructure\Providers\VehiclesServiceProvider::class,
    App\Vehicles\Templates\Application\TemplatesServiceProvider::class,
    App\Vehicles\Import\Infrastructure\Providers\ImportServiceProvider::class,
    App\Vehicles\Import\Infrastructure\Providers\ImportEventServiceProvider::class,
    App\Vehicles\Export\Infrastructure\Providers\ExportServiceProvider::class,
];
