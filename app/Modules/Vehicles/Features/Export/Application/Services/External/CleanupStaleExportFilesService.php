<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\Services\External;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Files\ExportFileStorageInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;

/**
 * Удаляет устаревшие файлы экспорта каталога с настроенного storage-диска.
 */
final readonly class CleanupStaleExportFilesService implements CleanupStaleExportFilesServiceInterface
{
    /**
     * Инициализирует файловый порт экспорта.
     */
    public function __construct(
        private ExportFileStorageInterface $files,
    ) {}

    /**
     * Ограничение по enum-префиксам и по подпапке — намеренно, а не «весь диск»:
     * 'output.directory' на этом диске может соседствовать с другим контентом
     * (Import кладёт отчёты об ошибках в отдельный каталог dan-vehicles/import),
     * поэтому неявная зависимость от постороннего файла в той же папке — не то, ради чего
     * стоит рисковать удалением чужого файла.
     */
    public function cleanup(): int
    {
        $disk = (string) config('vehicles.export.output.disk', 'local');
        $directory = (string) config('vehicles.export.output.directory', 'dan-vehicles/export');
        $retentionHours = (int) config('vehicles.export.output.retention_hours', 24);
        $threshold = now()->subHours($retentionHours)->getTimestamp();

        $deleted = 0;

        foreach ($this->files->files(
            disk: $disk,
            directory: $directory,
        ) as $path) {
            $matchesExportFilePattern = $this->matchesExportFilePattern($path);

            if (! $matchesExportFilePattern) {
                continue;
            }

            $lastModified = $this->files->lastModified(
                disk: $disk,
                path: $path,
            );

            if ($lastModified > $threshold) {
                continue;
            }

            $this->files->delete(
                disk: $disk,
                path: $path,
            );
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Проверяет, похож ли файл на сгенерированный экспорт Vehicles.
     */
    private function matchesExportFilePattern(string $path): bool
    {
        $fileName = basename($path);

        foreach ($this->filePatterns() as $pattern) {
            if (fnmatch($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function filePatterns(): array
    {
        $toFilePattern = static fn (ExportTypeEnum $type): string => $type->filePrefix().'-*.xlsx';

        return array_map(
            $toFilePattern,
            ExportTypeEnum::cases(),
        );
    }
}
