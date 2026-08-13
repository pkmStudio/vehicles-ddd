<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\CleanupExternalImportFileServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\AbstractImportCompleted;

/**
 * Реагирует на финальное событие импорта и запускает очистку внешнего файла.
 */
final readonly class CleanupExternalImportFileListener
{
    /**
     * Инициализирует service очистки внешнего import-файла.
     *
     * Шаги:
     * 1) Сохранить application service, который владеет cleanup-сценарием.
     */
    public function __construct(
        private CleanupExternalImportFileServiceInterface $service,
    ) {}

    /**
     * Очистить файл только для импортов, у которых есть внешний operationId.
     *
     * Шаги:
     * 1) Взять operation id из события завершения импорта.
     * 2) Делегировать cleanup service решение, есть ли связанный внешний файл.
     */
    public function handle(AbstractImportCompleted $event): void
    {
        $this->service->cleanup($event->operationId);
    }
}
