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
    /**
     * Получает Laravel filesystem factory для удаления import-файлов.
     *
     * Шаги:
     * 1. Сохраняет factory, выбирающую disk в runtime.
     * 2. Оставляет delete operation в методе domain port-а.
     */
    public function __construct(
        private FilesystemFactory $filesystems,
    ) {}

    /**
     * Удаляет файл с указанного disk.
     *
     * Шаги:
     * 1. Выбирает Laravel filesystem disk из cleanup metadata.
     * 2. Удаляет файл по исходному path.
     */
    public function delete(string $disk, string $path): void
    {
        $this->filesystems->disk($disk)->delete($path);
    }
}
