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
     *
     * Шаги:
     * 1) Попробовать получить disk из Laravel Storage.
     * 2) Вернуть true, если disk доступен.
     * 3) Вернуть false, если disk не настроен.
     */
    public function diskExists(string $disk): bool;

    /**
     * Проверяет, что файл существует на Storage disk.
     *
     * Шаги:
     * 1) Получить указанный Storage disk.
     * 2) Проверить существование path.
     * 3) Вернуть результат проверки.
     */
    public function fileExists(string $disk, string $path): bool;
}
