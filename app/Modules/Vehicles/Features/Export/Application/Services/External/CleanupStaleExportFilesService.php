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
     *
     * Шаги:
     * 1) Сохранить storage port, через который cleanup читает metadata и удаляет файлы.
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
     *
     * Шаги:
     * 1) Прочитать disk, directory и retention threshold из config.
     * 2) Перебрать файлы настроенного export-каталога.
     * 3) Пропустить файлы, не похожие на export artifacts Vehicles.
     * 4) Пропустить файлы моложе retention threshold.
     * 5) Удалить устаревший artifact и вернуть количество удаленных файлов.
     */
    public function cleanup(): int
    {
        $disk = (string) config('vehicles.export.output.disk');
        $directory = (string) config('vehicles.export.output.directory');
        $retentionHours = (int) config('vehicles.export.output.retention_hours');
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
     *
     * Шаги:
     * 1) Взять basename пути.
     * 2) Сравнить имя файла с каждым разрешенным export pattern.
     * 3) Вернуть true при первом совпадении.
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
     * Возвращает glob patterns файлов экспорта Vehicles.
     *
     * Шаги:
     * 1) Преобразовать каждый `ExportTypeEnum` в файловый prefix.
     * 2) Добавить wildcard timestamp/id части имени файла.
     *
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
