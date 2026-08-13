<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

interface ExternalImportCacheServiceInterface
{
    /**
     * Атомарно принимает operation id внешнего import-запроса.
     *
     * Шаги:
     * 1. Пытается создать idempotency marker для operation id.
     * 2. Возвращает `true`, только если marker создан впервые.
     */
    public function accept(string $operationId): bool;

    /**
     * Удаляет idempotency marker после ошибки запуска import workflow.
     *
     * Шаги:
     * 1. Строит key принятого operation id.
     * 2. Удаляет marker, чтобы повтор broker-сообщения мог снова запустить workflow.
     */
    public function forgetAccepted(string $operationId): void;

    /**
     * Запоминает исходный файл для удаления после завершения import workflow.
     *
     * Шаги:
     * 1. Берет disk/path из валидированного внешнего request DTO.
     * 2. Сохраняет cleanup metadata по operation id до completion event.
     */
    public function rememberCleanup(ExternalImportFileRequestDTO $request): void;

    /**
     * Забирает cleanup metadata для завершенного import operation id.
     *
     * Шаги:
     * 1. Читает и удаляет сохраненную metadata по operation id.
     * 2. Возвращает cleanup DTO или `null`, если cleanup не требовался.
     */
    public function pullCleanup(string $operationId): ?ExternalImportFileCleanupDTO;
}
