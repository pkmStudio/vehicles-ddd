<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation;

use App\Modules\Applicability\Features\Calculation\Domain\Enums\CalculationCompletionStatusEnum;

final readonly class CalculationCompletionNotificationDTO
{
    public function __construct(
        public CalculationCompletionStatusEnum $status,
        public string $runId,
        public int $processedKits,
        public int $calculatedKits,
        public int $skippedKits,
        public int $failedKits,
        public ?string $failuresReportPath = null,
        public ?string $failuresReportDisk = null,
    ) {}

    /**
     * @return array{status: string, run_id: string, processed_kits: int, calculated_kits: int, skipped_kits: int, failed_kits: int, failures_report_path: ?string, failures_report_disk: ?string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'run_id' => $this->runId,
            'processed_kits' => $this->processedKits,
            'calculated_kits' => $this->calculatedKits,
            'skipped_kits' => $this->skippedKits,
            'failed_kits' => $this->failedKits,
            'failures_report_path' => $this->failuresReportPath,
            'failures_report_disk' => $this->failuresReportDisk,
        ];
    }
}
