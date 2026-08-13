<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Files;

/**
 * Порт файлового хранилища для технических операций импорта.
 */
interface ImportFileStorageInterface
{
    /**
     * Удаляет файл импорта с указанного storage-диска.
     *
     * Шаги:
     * 1) Выбрать storage disk.
     * 2) Удалить файл по path.
     */
    public function delete(string $disk, string $path): void;
}
