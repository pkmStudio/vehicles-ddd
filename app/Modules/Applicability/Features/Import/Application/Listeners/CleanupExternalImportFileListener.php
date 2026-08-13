<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Application\Listeners;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use App\Modules\Applicability\Features\Import\Domain\Events\AbstractImportCompleted;

final readonly class CleanupExternalImportFileListener
{
    /**
     * Получает зависимости удаления внешнего import-файла после завершения workflow.
     *
     * Шаги:
     * 1. Сохраняет cache port, где лежит cleanup metadata по operation id.
     * 2. Сохраняет storage port, который удаляет файл на исходном disk.
     */
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ExternalImportFileStorageInterface $storage,
    ) {}

    /**
     * Удаляет внешний import-файл, если запуск был помечен как cleanup-after-import.
     *
     * Шаги:
     * 1. Игнорирует локальные события без operation id.
     * 2. Забирает cleanup metadata из cache по operation id.
     * 3. Если metadata нет, ничего не удаляет.
     * 4. Удаляет исходный файл через storage port по сохраненным disk и path.
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
