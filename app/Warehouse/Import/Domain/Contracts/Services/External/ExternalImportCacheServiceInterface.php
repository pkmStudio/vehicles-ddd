<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Domain\Contracts\Services\External;

use App\Warehouse\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Warehouse\Import\Domain\DTOs\ExternalImportFileRequestDTO;

/**
 * Порт идемпотентности и отложенной очистки файла внешних запусков Warehouse-импорта.
 */
interface ExternalImportCacheServiceInterface
{
    /**
     * Принимает runId только один раз в пределах cache TTL.
     */
    public function accept(string $runId): bool;

    /**
     * Снимает флаг принятого runId после неуспешного запуска — повтор сообщения из брокера
     * сможет попробовать снова.
     */
    public function forgetAccepted(string $runId): void;

    /**
     * Запоминает disk+path исходного файла, чтобы удалить его после завершения импорта.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void;

    /**
     * Забирает и удаляет из cache запомненное задание на очистку файла для runId, если оно есть.
     */
    public function pullCleanup(string $runId): ?ExternalImportFileCleanupDTO;
}
