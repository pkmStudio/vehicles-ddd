<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Application\Listeners;

use App\Warehouse\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Warehouse\Import\Domain\Events\AbstractImportCompleted;
use Illuminate\Support\Facades\Storage;

/**
 * Удаляет исходный файл внешнего Warehouse-импорта после того, как импорт реально завершился.
 * No-op для консольного запуска — там `rememberCleanup()` не вызывался, `pullCleanup()` вернёт
 * null.
 */
final readonly class CleanupExternalImportFileListener
{
    /**
     * Получает cache-сервис с отложенным заданием очистки.
     */
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
    ) {}

    /**
     * Этот метод удаляет файл, если для этого runId было запомнено задание на очистку.
     */
    public function handle(AbstractImportCompleted $event): void
    {
        if ($event->runId === null) {
            return;
        }

        $cleanup = $this->cache->pullCleanup($event->runId);

        if ($cleanup === null) {
            return;
        }

        Storage::disk($cleanup->disk)->delete($cleanup->path);
    }
}
