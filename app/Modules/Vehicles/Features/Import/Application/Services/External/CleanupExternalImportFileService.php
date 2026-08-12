<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\External;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Files\ImportFileStorageInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\CleanupExternalImportFileServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;

/**
 * Выполняет отложенную очистку файлов, когда импорт уже завершился.
 */
final readonly class CleanupExternalImportFileService implements CleanupExternalImportFileServiceInterface
{
    /**
     * Инициализирует cache-порт с инструкциями отложенной очистки.
     *
     * Шаги:
     * 1) Сохранить cache service, из которого cleanup достает disk/path по operation id.
     * 2) Сохранить storage port, который удаляет исходный import-файл.
     */
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ImportFileStorageInterface $files,
    ) {}

    /**
     * Забрать инструкцию очистки по operationId и удалить исходный файл.
     *
     * Шаги:
     * 1) Если operation id отсутствует — выйти без действий.
     * 2) Забрать cleanup-инструкцию из cache.
     * 3) Если инструкции нет — выйти без действий.
     * 4) Удалить файл через storage port по сохраненным disk/path.
     */
    public function cleanup(?string $operationId): void
    {
        if ($operationId === null || $operationId === '') {
            return;
        }

        $cleanup = $this->cache->pullCleanup($operationId);
        if ($cleanup === null) {
            return;
        }

        $this->files->delete(
            disk: $cleanup->disk,
            path: $cleanup->path,
        );
    }
}
