<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Files;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Files\ExportFileStorageInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Реализует файловые операции экспорта через Laravel Storage.
 */
final readonly class LaravelExportFileStorage implements ExportFileStorageInterface
{
    /**
     * Возвращает файлы указанной директории storage-диска.
     *
     * Шаги:
     * - Выбрать Laravel storage disk по имени.
     * - Вернуть список файлов в указанной директории.
     *
     * @return array<int, string>
     */
    public function files(string $disk, string $directory): array
    {
        return Storage::disk($disk)->files($directory);
    }

    /**
     * Возвращает timestamp последнего изменения файла.
     *
     * Шаги:
     * - Выбрать Laravel storage disk по имени.
     * - Запросить timestamp последнего изменения пути.
     */
    public function lastModified(string $disk, string $path): int
    {
        return Storage::disk($disk)->lastModified($path);
    }

    /**
     * Удаляет файл экспорта с указанного storage-диска.
     *
     * Шаги:
     * - Выбрать Laravel storage disk по имени.
     * - Удалить файл экспорта по переданному пути.
     */
    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }
}
