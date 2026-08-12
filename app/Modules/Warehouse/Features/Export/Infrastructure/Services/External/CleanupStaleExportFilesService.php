<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Services\External;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Удаляет старые файлы Warehouse-экспорта из настроенной директории Storage.
 */
final readonly class CleanupStaleExportFilesService implements CleanupStaleExportFilesServiceInterface
{
    /**
     * Шаблоны файлов, которые создают адаптеры Warehouse-экспорта.
     */
    private const array FILE_PATTERNS = [
        'warehouse-nomenclature-*.xlsx',
        'warehouse-kits-*.xlsx',
        'warehouse-wiper-adapter-audit-*.xlsx',
    ];

    /**
     * Удаляет сгенерированные файлы старше retention-порога.
     *
     * Шаги:
     * 1) Прочитать disk, директорию и retention из warehouse export config.
     * 2) Обойти файлы директории и оставить только известные имена экспортов.
     * 3) Сравнить lastModified с порогом устаревания.
     * 4) Удалить устаревшие файлы и вернуть счётчик удалений.
     */
    public function cleanup(): int
    {
        $disk = (string) config(
            key: 'warehouse.export.output.disk',
            default: 'local',
        );
        $directory = (string) config(
            key: 'warehouse.export.output.directory',
            default: 'exports',
        );
        $retentionHours = (int) config(
            key: 'warehouse.export.output.retention_hours',
            default: 24,
        );
        $threshold = now()->subHours($retentionHours)->getTimestamp();

        $storage = Storage::disk(
            name: $disk,
        );
        $deleted = 0;

        foreach ($storage->files($directory) as $path) {
            $matchesExportFilePattern = $this->matchesExportFilePattern($path);

            if (! $matchesExportFilePattern) {
                continue;
            }

            $lastModified = $storage->lastModified($path);

            if ($lastModified > $threshold) {
                continue;
            }

            $storage->delete($path);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Проверяет имя файла на соответствие известным паттернам Warehouse-экспорта.
     */
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
