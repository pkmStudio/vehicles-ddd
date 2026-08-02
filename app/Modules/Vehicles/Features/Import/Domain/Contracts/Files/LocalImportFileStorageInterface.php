<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Files;

/**
 * Порт проверки локального файла импорта Vehicles в Laravel Storage.
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
