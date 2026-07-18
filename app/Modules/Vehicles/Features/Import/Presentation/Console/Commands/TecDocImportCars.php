<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Presentation\Console\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\EngineModificationReadinessGateInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ManufacturerCommandImportInterface;
use Illuminate\Console\Command;

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
    public function handle(EngineModificationReadinessGateInterface $gate): void
    {
        $gate->reset();

        $path = storage_path('vehicles/manufacturers.csv');
        app(ManufacturerCommandImportInterface::class)->import($path);
        $this->info('Команда запустилась и отправило исполнение в очередь');
    }
}
