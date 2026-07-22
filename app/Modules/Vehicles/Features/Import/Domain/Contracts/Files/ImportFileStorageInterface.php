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
     */
    public function delete(string $disk, string $path): void;
}
