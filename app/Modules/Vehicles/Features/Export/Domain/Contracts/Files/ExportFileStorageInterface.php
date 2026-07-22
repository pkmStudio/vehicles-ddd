<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Files;

/**
 * Порт файлового хранилища для технических операций экспорта.
 */
interface ExportFileStorageInterface
{
    /**
     * Возвращает файлы указанной директории storage-диска.
     *
     * @return array<int, string>
     */
    public function files(string $disk, string $directory): array;

    /**
     * Возвращает timestamp последнего изменения файла.
     */
    public function lastModified(string $disk, string $path): int;

    /**
     * Удаляет файл экспорта с указанного storage-диска.
     */
    public function delete(string $disk, string $path): void;
}
