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
     *
     * Шаги:
     * 1) Получить Laravel Storage disk.
     * 2) Выполнить удаление файла по path.
     * 3) Завершить без ошибки, если adapter считает удаление успешным.
     */
    public function delete(string $disk, string $path): void;
}
