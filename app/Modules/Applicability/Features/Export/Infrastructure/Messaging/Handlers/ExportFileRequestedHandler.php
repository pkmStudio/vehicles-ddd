<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Handlers;

use App\Modules\Applicability\Features\Export\Application\UseCases\External\StartExportUseCase;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Validators\ExportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;

final readonly class ExportFileRequestedHandler
{
    /**
     * Получает зависимости RabbitMQ handler-а export request.
     *
     * Шаги:
     * 1. Сохраняет use case запуска export workflow.
     * 2. Сохраняет validator raw payload из broker-сообщения.
     */
    public function __construct(
        private StartExportUseCase $useCase,
        private ExportFileRequestedPayloadValidator $validator,
    ) {}

    /**
     * Валидирует RabbitMQ payload и запускает export применяемости.
     *
     * Шаги:
     * 1. Создает validator для raw payload.
     * 2. При ошибке validation пишет actionable error и отбрасывает сообщение без retry.
     * 3. Собирает локальный `ExportFileRequestDTO` из validated payload и config disk.
     * 4. Передает DTO в use case запуска export workflow.
     */
    public function handle(array $data): void
    {
        $validator = $this->validator->make($data);

        if ($validator->fails()) {
            Log::error('RabbitMQ: Applicability export file request payload validation failed', [
                'invalid_keys' => array_keys($validator->errors()->toArray()),
            ]);

            return;
        }

        $data = $validator->validated();

        $this->useCase->execute(new ExportFileRequestDTO(
            userId: (int) $data['user_id'],
            operationId: (string) $data['operation_id'],
            exportType: ExportTypeEnum::from((string) $data['export_type']),
            disk: (string) config('applicability.export.output.disk', 'local'),
        ));
    }
}
