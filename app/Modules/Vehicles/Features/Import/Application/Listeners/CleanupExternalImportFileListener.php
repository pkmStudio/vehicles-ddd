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
    public function __construct(
        private CleanupExternalImportFileServiceInterface $service,
    ) {}

    /**
     * Очистить файл только для импортов, у которых есть внешний runId.
     */
    public function handle(AbstractImportCompleted $event): void
    {
        $this->service->cleanup($event->runId);
    }
}
