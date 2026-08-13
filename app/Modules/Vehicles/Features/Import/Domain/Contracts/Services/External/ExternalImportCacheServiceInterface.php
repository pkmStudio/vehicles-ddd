<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

/**
 * Хранит техническое состояние внешнего запуска импорта в cache.
 */
interface ExternalImportCacheServiceInterface
{
    /**
     * Атомарно принять operationId; вернуть false, если такой запуск уже был принят.
     *
     * Шаги:
     * 1) Сформировать idempotency key из request operationId.
     * 2) Атомарно записать accepted marker.
     * 3) Вернуть false для повторной доставки.
     */
    public function accept(ExternalImportFileRequestDTO $request): bool;

    /**
     * Убрать отметку принятого operationId после ошибки запуска.
     *
     * Шаги:
     * 1) Сформировать idempotency key operationId.
     * 2) Удалить accepted marker из cache.
     */
    public function forgetAccepted(string $operationId): void;

    /**
     * Запомнить, какой исходный файл нужно удалить после завершения импорта.
     *
     * Шаги:
     * 1) Собрать cleanup DTO из request.
     * 2) Сохранить cleanup instruction по operationId.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void;

    /**
     * Забрать и удалить инструкцию очистки по operationId.
     *
     * Шаги:
     * 1) Найти cleanup instruction по operationId.
     * 2) Удалить instruction из cache.
     * 3) Вернуть cleanup DTO или null.
     */
    public function pullCleanup(string $operationId): ?ExternalImportFileCleanupDTO;
}
