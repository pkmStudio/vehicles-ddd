<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Storage;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Storage\LocalImportFileStorageInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Laravel Storage adapter для проверки локальных Warehouse import-файлов.
 */
final readonly class LaravelLocalImportFileStorage implements LocalImportFileStorageInterface
{
    /**
     * Проверяет наличие Storage disk в Laravel config.
     *
     * Шаги:
     * 1) Прочитать список disks из filesystems config.
     * 2) Проверить наличие имени disk.
     * 3) Вернуть boolean для use case публикации.
     */
    public function diskExists(string $disk): bool
    {
        return array_key_exists($disk, (array) config('filesystems.disks', []));
    }

    /**
     * Проверяет существование файла на указанном Storage disk.
     *
     * Шаги:
     * 1) Получить Laravel Storage disk.
     * 2) Проверить exists(path).
     * 3) Вернуть boolean для use case публикации.
     */
    public function fileExists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
