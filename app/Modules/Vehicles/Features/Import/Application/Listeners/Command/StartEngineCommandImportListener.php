<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners\Command;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineCommandImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;

/**
 * Запускает command import двигателей после завершения импорта производителей.
 */
final readonly class StartEngineCommandImportListener
{
    /**
     * Инициализирует adapter command import-а двигателей.
     *
     * Шаги:
     * 1) Сохранить import port, который читает локальный TecDoc CSV.
     */
    public function __construct(
        private EngineCommandImportInterface $import,
    ) {}

    /**
     * Обрабатывает событие завершения import-а производителей.
     *
     * Шаги:
     * 1) Собрать путь к локальному CSV двигателей.
     * 2) Запустить command import через import port.
     */
    public function handle(ManufacturerCommandImported $event): void
    {
        $path = storage_path('vehicles/engines.csv');
        $this->import->import($path);
    }
}
