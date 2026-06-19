<?php

declare(strict_types=1);

namespace App\Vehicles\Presentation\Console\Commands;

use App\Vehicles\Application\Listeners\EngineModificationReadinessSubscriber;
use App\Vehicles\Infrastructure\Imports\ManufacturerCommandImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class TecDocImportCars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tecDoc-import-cars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Приводит данные ТС к виду ТекДок';

    /**
     * 1. Сбрасываем флаги синхронизации на случай прерванного предыдущего запуска
     * 2. Импортирует производителей
     * 3. По завершении вызывает событие
     * 4. Слушатели StartVehicleImport и StartEngineImport запускают импорт ТС и двигателей
     * 5. По готовности ТС импортируются модификации
     * 6. По готовности модификаций и двигателей по событию запускается импорт связи между модификацией и двигателями
     */
    public function handle(): void
    {
        EngineModificationReadinessSubscriber::clearFlags();

        $path = storage_path('vehicles/manufacturers.csv');
        Excel::import(app(ManufacturerCommandImport::class), $path);
        $this->info('Команда запустилась и отправило исполнение в очередь');
    }
}
