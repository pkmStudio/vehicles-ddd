<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Listeners;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use App\Modules\Warehouse\Features\Import\Domain\Events\AbstractImportCompleted;

/**
 * Удаляет исходный файл внешнего Warehouse-импорта после того, как импорт реально завершился.
 * No-op для консольного запуска — там `rememberCleanup()` не вызывался, `pullCleanup()` вернёт
 * null.
 */
final readonly class CleanupExternalImportFileListener
{
    /**
     * Получает cache-сервис с отложенным заданием очистки.
     *
     * Шаги:
     * 1) Принять порт cache, где хранится cleanup-задание внешнего импорта.
     * 2) Принять порт storage, который умеет удалить исходный файл.
     */
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ExternalImportFileStorageInterface $storage,
    ) {}

    /**
     * Этот метод удаляет файл, если для этого operationId было запомнено задание на очистку.
     *
     * Шаги:
     * 1) Пропустить консольные импорты без operationId.
     * 2) Забрать и удалить cleanup-задание из cache по operationId.
     * 3) Завершить обработку, если задания нет.
     * 4) Удалить исходный файл через storage-порт.
     */
    public function handle(AbstractImportCompleted $event): void
    {
        if ($event->operationId === null) {
            return;
        }

        $cleanup = $this->cache->pullCleanup($event->operationId);

        if ($cleanup === null) {
            return;
        }

        $this->storage->delete($cleanup->disk, $cleanup->path);
    }
}
