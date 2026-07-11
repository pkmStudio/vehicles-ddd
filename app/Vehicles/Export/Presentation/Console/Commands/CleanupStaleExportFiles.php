<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Presentation\Console\Commands;

use App\Vehicles\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use Illuminate\Console\Command;

/**
 * Safety-net очистка сгенерированных файлов экспорта: основной путь удаления —
 * принимающий сервис, забравший файл по скачиванию; эта команда подчищает то,
 * что осталось старше retention-порога. Запускается по расписанию
 * (routes/console.php).
 */
final class CleanupStaleExportFiles extends Command
{
    protected $signature = 'vehicles:export-cleanup-stale-files';

    protected $description = 'Удаляет сгенерированные файлы экспорта каталога старше retention-порога';

    public function handle(CleanupStaleExportFilesServiceInterface $service): int
    {
        $deleted = $service->cleanup();

        $this->info("Удалено файлов: {$deleted}");

        return self::SUCCESS;
    }
}
