<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Files;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Files\ImportFileStorageInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Реализует файловые операции импорта через Laravel Storage.
 */
final readonly class LaravelImportFileStorage implements ImportFileStorageInterface
{
    /**
     * Удаляет файл импорта с указанного storage-диска.
     */
    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }
}
