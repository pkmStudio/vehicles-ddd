<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Storage;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Laravel Storage-реализация удаления исходных файлов внешнего Warehouse-импорта.
 */
final readonly class LaravelExternalImportFileStorage implements ExternalImportFileStorageInterface
{
    /**
     * Удаляет файл на указанном disk.
     */
    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }
}
