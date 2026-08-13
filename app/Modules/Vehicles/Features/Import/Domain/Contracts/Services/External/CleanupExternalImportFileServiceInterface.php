<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External;

/**
 * Очищает файлы внешнего импорта после финального события завершения прогона.
 */
interface CleanupExternalImportFileServiceInterface
{
    /**
     * Удалить исходный файл, связанный с operationId, если такая инструкция была сохранена.
     *
     * Шаги:
     * 1) Получить cleanup instruction из cache по operationId.
     * 2) Удалить исходный файл через storage adapter, если instruction есть.
     */
    public function cleanup(?string $operationId): void;
}
