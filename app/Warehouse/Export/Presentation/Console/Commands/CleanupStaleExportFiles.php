<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Presentation\Console\Commands;

use App\Warehouse\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use Illuminate\Console\Command;

/**
 * Artisan-команда ручной или плановой очистки старых файлов Warehouse-экспорта.
 */
final class CleanupStaleExportFiles extends Command
{
    protected $signature = 'warehouse:export-cleanup-stale-files';

    protected $description = 'Удаляет сгенерированные файлы Warehouse-экспорта старше retention-порога';

    /**
     * Делегирует очистку сервису и выводит количество удалённых файлов.
     */
    public function handle(CleanupStaleExportFilesServiceInterface $service): int
    {
        $deleted = $service->cleanup();

        $this->info("Удалено файлов: {$deleted}");

        return self::SUCCESS;
    }
}
