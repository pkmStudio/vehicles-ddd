<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners\Command;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\Command\EngineModificationImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\EnginesAndModificationsReady;

/**
 * Запускает command import связей двигатель-модификация после rendezvous engines/modifications.
 */
final readonly class StartEngineModificationCommandImportListener
{
    /**
     * Инициализирует adapter command import-а связей двигатель-модификация.
     *
     * Шаги:
     * 1) Сохранить import port, который читает локальный TecDoc CSV связей.
     */
    public function __construct(
        private EngineModificationImportInterface $import,
    ) {}

    /**
     * Обрабатывает событие готовности двигателей и модификаций.
     *
     * Шаги:
     * 1) Собрать путь к локальному CSV связей двигатель-модификация.
     * 2) Запустить command import через import port.
     */
    public function handle(EnginesAndModificationsReady $event): void
    {
        $path = storage_path('vehicles/engine_modification.csv');
        $this->import->import($path);
    }
}
