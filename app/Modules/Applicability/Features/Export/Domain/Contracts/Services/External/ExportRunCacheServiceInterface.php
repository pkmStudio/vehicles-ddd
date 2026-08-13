<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External;

interface ExportRunCacheServiceInterface
{
    /**
     * Атомарно принимает operation id внешнего export-запроса.
     *
     * Шаги:
     * 1. Пытается записать idempotency marker для operation id.
     * 2. Возвращает `true`, только если marker был создан впервые.
     */
    public function accept(string $operationId): bool;

    /**
     * Удаляет idempotency marker после сбоя запуска export workflow.
     *
     * Шаги:
     * 1. Строит cache key для operation id.
     * 2. Удаляет marker, чтобы сообщение можно было безопасно повторить.
     */
    public function forgetAccepted(string $operationId): void;
}
