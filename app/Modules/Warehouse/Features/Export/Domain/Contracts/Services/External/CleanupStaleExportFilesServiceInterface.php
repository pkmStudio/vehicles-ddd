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
     * @return int количество удалённых файлов
     */
    public function cleanup(): int;
}
