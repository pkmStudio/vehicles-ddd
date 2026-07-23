<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Storage;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Laravel filesystem-реализация удаления исходного файла внешнего импорта.
 */
final readonly class LaravelExternalImportFileStorage implements ExternalImportFileStorageInterface
{
    public function __construct(
        private FilesystemFactory $filesystems,
    ) {}

    /**
     * Удаляет файл с указанного disk.
     */
    public function delete(string $disk, string $path): void
    {
        $this->filesystems->disk($disk)->delete($path);
    }
}
