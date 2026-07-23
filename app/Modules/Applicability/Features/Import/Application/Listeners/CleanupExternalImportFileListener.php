<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Application\Listeners;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use App\Modules\Applicability\Features\Import\Domain\Events\AbstractImportCompleted;

final readonly class CleanupExternalImportFileListener
{
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ExternalImportFileStorageInterface $storage,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        if ($event->runId === null) {
            return;
        }

        $cleanup = $this->cache->pullCleanup($event->runId);
        if ($cleanup === null) {
            return;
        }

        $this->storage->delete($cleanup->disk, $cleanup->path);
    }
}
