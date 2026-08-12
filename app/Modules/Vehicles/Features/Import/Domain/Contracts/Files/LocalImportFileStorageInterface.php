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
     *
     * Шаги:
     * 1) Проверить наличие disk в Laravel filesystem config.
     * 2) Вернуть boolean-результат проверки.
     */
    public function diskExists(string $disk): bool;

    /**
     * Проверяет, что файл существует на Storage disk.
     *
     * Шаги:
     * 1) Выбрать storage disk.
     * 2) Проверить существование файла по path.
     */
    public function fileExists(string $disk, string $path): bool;
}
