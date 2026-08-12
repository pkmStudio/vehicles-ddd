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
     * Шаги:
     * 1) Прочитать список файлов на указанном disk/directory.
     * 2) Вернуть storage paths без привязки к concrete filesystem adapter.
     *
     * @return array<int, string>
     */
    public function files(string $disk, string $directory): array;

    /**
     * Возвращает timestamp последнего изменения файла.
     *
     * Шаги:
     * 1) Прочитать metadata файла на указанном disk/path.
     * 2) Вернуть Unix timestamp последнего изменения.
     */
    public function lastModified(string $disk, string $path): int;

    /**
     * Удаляет файл экспорта с указанного storage-диска.
     *
     * Шаги:
     * 1) Передать disk/path concrete filesystem adapter-у.
     * 2) Удалить файл, если adapter поддерживает такую операцию.
     */
    public function delete(string $disk, string $path): void;
}
