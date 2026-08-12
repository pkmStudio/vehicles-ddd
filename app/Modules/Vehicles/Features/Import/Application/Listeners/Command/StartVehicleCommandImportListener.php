<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners\Command;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\VehicleCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;

/**
 * Запускает command import автомобилей после завершения импорта производителей.
 */
final readonly class StartVehicleCommandImportListener
{
    /**
     * Инициализирует adapter command import-а автомобилей.
     *
     * Шаги:
     * 1) Сохранить import port, который читает локальный TecDoc CSV.
     */
    public function __construct(
        private VehicleCommandImportInterface $import,
    ) {}

    /**
     * Обрабатывает событие завершения import-а производителей.
     *
     * Шаги:
     * 1) Собрать путь к локальному CSV автомобилей.
     * 2) Запустить command import через import port.
     */
    public function handle(ManufacturerCommandImported $event): void
    {
        $path = storage_path('vehicles/vehicles.csv');
        $this->import->import($path);
    }
}
