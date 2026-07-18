<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External;

/**
 * Порт идемпотентности внешних запусков Warehouse-экспорта.
 */
interface ExportRunCacheServiceInterface
{
    /**
     * Принимает runId только один раз в пределах cache TTL.
     */
    public function accept(string $runId): bool;

    /**
     * Снимает флаг принятого runId после неуспешного запуска.
     */
    public function forgetAccepted(string $runId): void;
}
