<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Storage;

/**
 * Порт проверки локального файла импорта Warehouse в Laravel Storage.
 */
interface LocalImportFileStorageInterface
{
    /**
     * Проверяет, что Storage disk настроен.
     */
    public function diskExists(string $disk): bool;

    /**
     * Проверяет, что файл существует на Storage disk.
     */
    public function fileExists(string $disk, string $path): bool;
}
