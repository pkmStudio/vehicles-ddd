<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners\Command;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\ModificationCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleCommandImported;

/**
 * Запускает command import модификаций после завершения импорта автомобилей.
 */
final readonly class StartModificationCommandImportListener
{
    /**
     * Инициализирует adapter command import-а модификаций.
     *
     * Шаги:
     * 1) Сохранить import port, который читает локальный TecDoc CSV.
     */
    public function __construct(
        private ModificationCommandImportInterface $import,
    ) {}

    /**
     * Обрабатывает событие завершения import-а автомобилей.
     *
     * Шаги:
     * 1) Собрать путь к локальному CSV модификаций.
     * 2) Запустить command import через import port.
     */
    public function handle(VehicleCommandImported $event): void
    {
        $path = storage_path('vehicles/modifications.csv');
        $this->import->import($path);
    }
}
