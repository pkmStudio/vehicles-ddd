<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External;

/**
 * Идемпотентность внешнего запроса на экспорт по runId. Симметрично
 * Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface,
 * но без rememberCleanup/pullCleanup — у Export нет входного файла для очистки.
 */
interface ExportRunCacheServiceInterface
{
    /**
     * Атомарно отметить runId как принятый. false — уже был принят (дубликат
     * доставки), повторный экспорт запускать не нужно.
     */
    public function accept(string $runId): bool;

    /**
     * Снять отметку принятого runId, чтобы повторная доставка сообщения
     * могла попробовать снова (используется при ошибке экспорта).
     */
    public function forgetAccepted(string $runId): void;
}
