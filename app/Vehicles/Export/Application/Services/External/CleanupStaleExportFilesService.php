<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services\External;

use App\Vehicles\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use Illuminate\Support\Facades\Storage;

final readonly class CleanupStaleExportFilesService implements CleanupStaleExportFilesServiceInterface
{
    /**
     * Шаблоны имён файлов, которые пишут VehicleMultiSheetExport/EngineMultiSheetExport
     * (см. sprintf в соответствующих export()). Ограничение по паттерну и по подпапке —
     * намеренно, а не «весь диск»: 'output.directory' на этом диске делят с другим
     * контентом (Import кладёт туда же отчёты об ошибках, 'exports/import-failures*.csv'),
     * поэтому неявная зависимость от постороннего файла в той же папке — не то, ради чего
     * стоит рисковать удалением чужого файла.
     */
    private const array FILE_PATTERNS = [
        'vehicle-catalog-*.xlsx',
        'engine-catalog-*.xlsx',
    ];

    public function cleanup(): int
    {
        $disk = (string) config('vehicles.export.output.disk', 'local');
        $directory = (string) config('vehicles.export.output.directory', 'exports');
        $retentionHours = (int) config('vehicles.export.output.retention_hours', 24);
        $threshold = now()->subHours($retentionHours)->getTimestamp();

        $storage = Storage::disk($disk);
        $deleted = 0;

        foreach ($storage->files($directory) as $path) {
            if (! $this->matchesExportFilePattern($path)) {
                continue;
            }

            if ($storage->lastModified($path) > $threshold) {
                continue;
            }

            $storage->delete($path);
            $deleted++;
        }

        return $deleted;
    }

    private function matchesExportFilePattern(string $path): bool
    {
        $fileName = basename($path);

        foreach (self::FILE_PATTERNS as $pattern) {
            if (fnmatch($pattern, $fileName)) {
                return true;
            }
        }

        return false;
    }
}
