<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\External;

use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

/**
 * Порт идемпотентности и отложенной очистки файла внешних запусков Warehouse-импорта.
 */
interface ExternalImportCacheServiceInterface
{
    /**
     * Принимает operationId только один раз в пределах cache TTL.
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает флаг принятого operationId после неуспешного запуска — повтор сообщения из брокера
     * сможет попробовать снова.
     */
    public function forgetAccepted(string $operationId): void;

    /**
     * Запоминает disk+path исходного файла, чтобы удалить его после завершения импорта.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void;

    /**
     * Забирает и удаляет из cache запомненное задание на очистку файла для operationId, если оно есть.
     */
    public function pullCleanup(string $operationId): ?ExternalImportFileCleanupDTO;
}
