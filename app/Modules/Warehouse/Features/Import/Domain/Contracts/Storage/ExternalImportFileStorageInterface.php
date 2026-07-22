<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Storage;

/**
 * Порт удаления исходных файлов внешнего Warehouse-импорта.
 */
interface ExternalImportFileStorageInterface
{
    /**
     * Удаляет файл на указанном disk.
     */
    public function delete(string $disk, string $path): void;
}
