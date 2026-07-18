<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\External;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\CleanupExternalImportFileServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Выполняет отложенную очистку файлов, когда импорт уже завершился.
 */
final readonly class CleanupExternalImportFileService implements CleanupExternalImportFileServiceInterface
{
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
    ) {}

    /**
     * Забрать инструкцию очистки по runId и удалить исходный файл.
     */
    public function cleanup(?string $runId): void
    {
        if ($runId === null || $runId === '') {
            return;
        }

        $cleanup = $this->cache->pullCleanup($runId);
        if ($cleanup === null) {
            return;
        }

        Storage::disk($cleanup->disk)->delete($cleanup->path);
    }
}
