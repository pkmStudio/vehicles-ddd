<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External;

/**
 * Очищает файлы внешнего импорта после финального события завершения прогона.
 */
interface CleanupExternalImportFileServiceInterface
{
    /**
     * Удалить исходный файл, связанный с runId, если такая инструкция была сохранена.
     */
    public function cleanup(?string $runId): void;
}
