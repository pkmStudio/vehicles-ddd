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
     */
    public function accept(string $operationId): bool;

    /**
     * Снимает флаг принятого operationId после неуспешного запуска.
     */
    public function forgetAccepted(string $operationId): void;
}
