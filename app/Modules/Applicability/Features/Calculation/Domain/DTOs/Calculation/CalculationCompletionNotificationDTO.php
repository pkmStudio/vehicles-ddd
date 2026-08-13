<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation;

use App\Modules\Applicability\Features\Calculation\Domain\Enums\CalculationCompletionStatusEnum;

final readonly class CalculationCompletionNotificationDTO
{
    /**
     * Создает payload уведомления о завершении расчета применяемости.
     */
    public function __construct(
        public CalculationCompletionStatusEnum $status,
        public string $operationId,
        public int $processedKits,
        public int $calculatedKits,
        public int $skippedKits,
        public int $failedKits,
        public ?string $failuresReportPath = null,
        public ?string $failuresReportDisk = null,
        public ?int $userId = null,
    ) {}

    /**
     * @return array{status: string, operation_id: string, processed_kits: int, calculated_kits: int, skipped_kits: int, failed_kits: int, failures_report_path: ?string, failures_report_disk: ?string, user_id: ?int}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'operation_id' => $this->operationId,
            'processed_kits' => $this->processedKits,
            'calculated_kits' => $this->calculatedKits,
            'skipped_kits' => $this->skippedKits,
            'failed_kits' => $this->failedKits,
            'failures_report_path' => $this->failuresReportPath,
            'failures_report_disk' => $this->failuresReportDisk,
            'user_id' => $this->userId,
        ];
    }
}
