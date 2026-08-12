<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Presentation\Console\Commands;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
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

    /**
     * Запустить safety-net очистку старых export-файлов.
     *
     * Шаги:
     * 1) Вызвать application service очистки stale export artifacts.
     * 2) Вывести количество удаленных файлов в console output.
     * 3) Вернуть успешный exit code команды.
     */
    public function handle(CleanupStaleExportFilesServiceInterface $service): int
    {
        $deleted = $service->cleanup();

        $this->info("Удалено файлов: {$deleted}");

        return self::SUCCESS;
    }
}
