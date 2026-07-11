<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Application\Services\External;

use App\Vehicles\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use Illuminate\Support\Facades\Storage;

final readonly class CleanupStaleExportFilesService implements CleanupStaleExportFilesServiceInterface
{
    /**
     * Шаблоны имён файлов, которые пишут VehicleMultiSheetExport/EngineMultiSheetExport
     * (см. sprintf в соответствующих export()). Ограничение по паттерну — намеренно, а
     * не «весь диск»: 'exports' — выделенный диск только под эти файлы (см. config/
     * vehicles-export.php), но неявная зависимость от постороннего контента на этом же
     * диске — не то, ради чего стоит рисковать удалением чужого файла.
     */
    private const array FILE_PATTERNS = [
        'vehicle-catalog-*.xlsx',
        'engine-catalog-*.xlsx',
    ];

    public function cleanup(): int
    {
        $disk = (string) config('vehicles-export.output.disk', 's3');
        $retentionHours = (int) config('vehicles-export.output.retention_hours', 24);
        $threshold = now()->subHours($retentionHours)->getTimestamp();

        $storage = Storage::disk($disk);
        $deleted = 0;

        foreach ($storage->files() as $path) {
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
