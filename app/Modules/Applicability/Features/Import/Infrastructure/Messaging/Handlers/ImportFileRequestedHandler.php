<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Messaging\Handlers;

use App\Modules\Applicability\Features\Import\Application\UseCases\External\StartExternalFileImportUseCase;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Applicability\Features\Import\Infrastructure\Messaging\Validators\ImportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Import\DTO\ImportFileRequested as WireImportFileRequested;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Import\Enums\ApplicabilityImportType;

final readonly class ImportFileRequestedHandler
{
    /**
     * Получает зависимости RabbitMQ handler-а import request.
     *
     * Шаги:
     * 1. Сохраняет use case запуска import workflow.
     * 2. Сохраняет validator raw payload из broker-сообщения.
     */
    public function __construct(
        private StartExternalFileImportUseCase $useCase,
        private ImportFileRequestedPayloadValidator $validator,
    ) {}

    /**
     * Валидирует RabbitMQ payload и запускает импорт файла применяемости.
     *
     * Шаги:
     * 1. Создает validator для raw payload.
     * 2. При ошибке validation пишет actionable error и отбрасывает сообщение без retry.
     * 3. Собирает package wire DTO из validated payload.
     * 4. Явно мапит package enum в локальный import enum.
     * 5. Собирает локальный `ExternalImportFileRequestDTO` с disk/path/cleanup flag.
     * 6. Передает DTO в use case внешнего import workflow.
     *
     * @param  array{user_id?: int|string, operation_id?: string, import_type?: string, disk?: string, path?: string, cleanup_after_import?: bool|int|string}  $data
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            $context = [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ];

            if (is_string($data['operation_id'] ?? null)) {
                $context['operation_id'] = $data['operation_id'];
            }

            Log::error('RabbitMQ: Applicability import file request payload validation failed', $context);

            return;
        }

        $validated = $validator->validated();
        $wirePayload = [
            'user_id' => (int) $validated['user_id'],
            'operation_id' => (string) $validated['operation_id'],
            'import_type' => (string) $validated['import_type'],
            'path' => (string) $validated['path'],
            'cleanup_after_import' => (bool) ($validated['cleanup_after_import'] ?? true),
        ];

        if (is_string($validated['disk'] ?? null)) {
            $wirePayload['disk'] = $validated['disk'];
        }

        $wireRequest = WireImportFileRequested::fromArray($wirePayload);

        $this->useCase->execute(new ExternalImportFileRequestDTO(
            userId: $wireRequest->userId,
            operationId: $wireRequest->operationId,
            importType: $this->importType($wireRequest->importType),
            disk: $wireRequest->disk,
            path: $wireRequest->path,
            cleanupAfterImport: $wireRequest->cleanupAfterImport,
        ));
    }

    /**
     * Мапит package wire enum в локальный enum import workflow.
     */
    private function importType(ApplicabilityImportType $type): ImportTypeEnum
    {
        return match ($type) {
            ApplicabilityImportType::KitApplicability => ImportTypeEnum::KitApplicability,
        };
    }
}
