<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\DTOs;

use App\Modules\Applicability\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;

final readonly class ImportCompletionNotificationDTO
{
    /**
     * Описывает payload результата import workflow для внешнего consumer-а.
     */
    public function __construct(
        public ImportCompletionStatusEnum $status,
        public ImportTypeEnum $importType,
        public int $userId,
        public string $operationId,
        public ?string $failuresReportPath = null,
        public ?string $failuresReportDisk = null,
    ) {}

    /**
     * Преобразует notification DTO в wire payload RabbitMQ-события.
     *
     * @return array{status: string, import_type: string, user_id: int, operation_id: string, failures_report_path: ?string, failures_report_disk: ?string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'import_type' => $this->importType->value,
            'user_id' => $this->userId,
            'operation_id' => $this->operationId,
            'failures_report_path' => $this->failuresReportPath,
            'failures_report_disk' => $this->failuresReportDisk,
        ];
    }
}
