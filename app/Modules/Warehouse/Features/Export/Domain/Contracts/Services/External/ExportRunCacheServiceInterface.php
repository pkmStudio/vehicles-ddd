<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External;

/**
 * Порт идемпотентности внешних запусков Warehouse-экспорта.
 */
interface ExportRunCacheServiceInterface
{
    /**
     * Принимает operationId только один раз в пределах cache TTL.
     *
     * Шаги:
     * 1) Собрать cache-ключ для operationId.
     * 2) Атомарно записать флаг принятого запуска с TTL.
     * 3) Вернуть false, если такой operationId уже был принят.
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает флаг принятого operationId после неуспешного запуска.
     *
     * Шаги:
     * 1) Собрать cache-ключ для operationId.
     * 2) Удалить флаг принятого запуска.
     * 3) Разрешить брокеру повторить неуспешный export workflow.
     */
    public function forgetAccepted(string $operationId): void;
}
