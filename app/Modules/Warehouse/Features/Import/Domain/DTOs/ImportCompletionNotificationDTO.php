<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs;

use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

/**
 * Payload события завершения Warehouse-импорта.
 */
final readonly class ImportCompletionNotificationDTO
{
    /**
     * Хранит wire-данные уведомления о результате Warehouse-импорта.
     */
    public function __construct(
        public ImportCompletionStatusEnum $status,
        public ImportTypeEnum $importType,
        public ?int $userId = null,
        public ?string $runId = null,
        public ?string $failuresReportPath = null,
        public ?string $failuresReportDisk = null,
    ) {}

    /**
     * Преобразует DTO в snake_case payload для rabbit-transport.
     *
     * @return array{status: string, import_type: string, user_id: ?int, run_id: ?string, failures_report_path: ?string, failures_report_disk: ?string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'import_type' => $this->importType->value,
            'user_id' => $this->userId,
            'run_id' => $this->runId,
            'failures_report_path' => $this->failuresReportPath,
            'failures_report_disk' => $this->failuresReportDisk,
        ];
    }
}
