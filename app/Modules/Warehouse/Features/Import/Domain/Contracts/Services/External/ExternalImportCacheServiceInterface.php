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
     *
     * Шаги:
     * 1) Построить cache key по operationId.
     * 2) Попробовать атомарно записать признак принятого запуска.
     * 3) Вернуть true только для первого принятого сообщения.
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает флаг принятого operationId после неуспешного запуска — повтор сообщения из брокера
     * сможет попробовать снова.
     *
     * Шаги:
     * 1) Построить cache key принятого operationId.
     * 2) Удалить этот ключ из cache.
     */
    public function forgetAccepted(string $operationId): void;

    /**
     * Запоминает disk+path исходного файла, чтобы удалить его после завершения импорта.
     *
     * Шаги:
     * 1) Извлечь operationId, disk и path из внешнего запроса.
     * 2) Собрать DTO задания очистки.
     * 3) Сохранить задание в cache до завершения импорта.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void;

    /**
     * Забирает и удаляет из cache запомненное задание на очистку файла для operationId, если оно есть.
     *
     * Шаги:
     * 1) Построить cache key cleanup-задания по operationId.
     * 2) Забрать значение из cache с удалением.
     * 3) Вернуть DTO очистки или null.
     */
    public function pullCleanup(string $operationId): ?ExternalImportFileCleanupDTO;
}
