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
     */
    public function diskExists(string $disk): bool
    {
        return array_key_exists($disk, (array) config('filesystems.disks', []));
    }

    /**
     * Проверяет существование файла на указанном Storage disk.
     */
    public function fileExists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
