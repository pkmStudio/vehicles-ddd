<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Handlers;

use App\Modules\Applicability\Features\Export\Application\UseCases\External\StartExportUseCase;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Validators\ExportFileRequestedPayloadValidator;
use Illuminate\Support\Facades\Log;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Export\DTO\ExportFileRequested as WireExportFileRequested;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Export\Enums\ApplicabilityExportType;

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
     * 3. Собирает package wire DTO из validated payload.
     * 4. Явно мапит package enum в локальный export enum.
     * 5. Собирает локальный `ExportFileRequestDTO` из wire DTO и config disk.
     * 6. Передает DTO в use case запуска export workflow.
     *
     * @param  array{user_id?: int|string, operation_id?: string, export_type?: string}  $data
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

            Log::error('RabbitMQ: Applicability export file request payload validation failed', $context);

            return;
        }

        $validated = $validator->validated();
        $wireRequest = WireExportFileRequested::fromArray([
            'user_id' => (int) $validated['user_id'],
            'operation_id' => (string) $validated['operation_id'],
            'export_type' => (string) $validated['export_type'],
        ]);

        $this->useCase->execute(new ExportFileRequestDTO(
            userId: $wireRequest->userId,
            operationId: $wireRequest->operationId,
            exportType: $this->exportType($wireRequest->exportType),
            disk: (string) config('applicability.export.output.disk', 'local'),
        ));
    }

    /**
     * Мапит package wire enum в локальный enum export workflow.
     */
    private function exportType(ApplicabilityExportType $type): ExportTypeEnum
    {
        return match ($type) {
            ApplicabilityExportType::VehicleKitApplicability => ExportTypeEnum::VehicleKitApplicability,
            ApplicabilityExportType::ModificationKitApplicability => ExportTypeEnum::ModificationKitApplicability,
        };
    }
}
