<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External;

/**
 * Порт очистки старых файлов Warehouse-экспорта.
 */
interface CleanupStaleExportFilesServiceInterface
{
    /**
     * Удаляет устаревшие файлы и возвращает количество удалённых.
     *
     * Шаги:
     * 1) Определить storage disk, директорию и retention-порог реализации.
     * 2) Найти файлы Warehouse-экспорта старше порога.
     * 3) Удалить подходящие файлы и вернуть их количество.
     *
     * @return int количество удалённых файлов
     */
    public function cleanup(): int;
}
