<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Safety-net очистка файлов экспорта старше retention-порога (см. config/vehicles/export.php).
Schedule::command('vehicles:export-cleanup-stale-files')->daily();

// Safety-net очистка файлов Warehouse-экспорта старше retention-порога.
Schedule::command('warehouse:export-cleanup-stale-files')->daily();
