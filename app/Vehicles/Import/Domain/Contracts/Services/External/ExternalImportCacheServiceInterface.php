<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\External;

use App\Vehicles\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Vehicles\Import\Domain\DTOs\ExternalImportFileRequestDTO;

/**
 * Хранит техническое состояние внешнего запуска импорта в cache.
 */
interface ExternalImportCacheServiceInterface
{
    /**
     * Атомарно принять runId; вернуть false, если такой запуск уже был принят.
     */
    public function accept(ExternalImportFileRequestDTO $request): bool;

    /**
     * Убрать отметку принятого runId после ошибки запуска.
     */
    public function forgetAccepted(string $runId): void;

    /**
     * Запомнить, какой исходный файл нужно удалить после завершения импорта.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void;

    /**
     * Забрать и удалить инструкцию очистки по runId.
     */
    public function pullCleanup(string $runId): ?ExternalImportFileCleanupDTO;
}
