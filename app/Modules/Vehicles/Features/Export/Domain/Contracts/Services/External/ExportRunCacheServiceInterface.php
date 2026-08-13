<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External;

/**
 * Идемпотентность внешнего запроса на экспорт по operationId. Симметрично
 * Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface,
 * но без rememberCleanup/pullCleanup — у Export нет входного файла для очистки.
 */
interface ExportRunCacheServiceInterface
{
    /**
     * Атомарно отметить operationId как принятый. false — уже был принят (дубликат
     * доставки), повторный экспорт запускать не нужно.
     *
     * Шаги:
     * 1) Попытаться атомарно записать operation id в cache.
     * 2) Вернуть false, если ключ уже существует.
     * 3) Вернуть true, если запрос принят впервые.
     */
    public function accept(string $operationId): bool;

    /**
     * Снять отметку принятого operationId, чтобы повторная доставка сообщения
     * могла попробовать снова (используется при ошибке экспорта).
     *
     * Шаги:
     * 1) Удалить cache-key принятого operation id.
     * 2) Позволить повторной доставке снова пройти accept gate.
     */
    public function forgetAccepted(string $operationId): void;
}
