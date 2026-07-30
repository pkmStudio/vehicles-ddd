<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Listeners;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Notifications\CalculationNotificationServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting\CalculationFailureReporterInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\CalculationCompletionStatusEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use Psr\Log\LoggerInterface;

/**
 * Пишет отчет об ошибках после завершения расчета применяемости.
 */
final readonly class ReportCalculationResultListener
{
    public function __construct(
        private CalculationFailureReporterInterface $reporter,
        private CalculationNotificationServiceInterface $notifications,
        private LoggerInterface $logger,
    ) {}

    /**
     * Сохраняет CSV с ошибками расчета и логирует итог запуска.
     */
    public function handle(KitApplicabilityRecalculated $event): void
    {
        $reportPath = $this->reporter->store($event->result);
        $reportDisk = (string) config('applicability.calculation.failures.disk', 'local');

        $this->notifications->notifyCalculationCompleted(new CalculationCompletionNotificationDTO(
            status: $reportPath === null
                ? CalculationCompletionStatusEnum::COMPLETED
                : CalculationCompletionStatusEnum::COMPLETED_WITH_FAILURES,
            operationId: $event->operationId,
            processedKits: $event->result->processedKits,
            calculatedKits: $event->result->calculatedKits,
            skippedKits: $event->result->skippedKits,
            failedKits: $event->result->failedKits,
            failuresReportPath: $reportPath,
            failuresReportDisk: $reportPath === null ? null : $reportDisk,
        ));

        if ($reportPath === null) {
            $this->logger->info('Applicability calculation completed', [
                'operation_id' => $event->operationId,
                'processed_kits' => $event->result->processedKits,
                'calculated_kits' => $event->result->calculatedKits,
                'skipped_kits' => $event->result->skippedKits,
                'failed_kits' => $event->result->failedKits,
            ]);

            return;
        }

        $this->logger->warning('Applicability calculation completed with failures', [
            'operation_id' => $event->operationId,
            'processed_kits' => $event->result->processedKits,
            'calculated_kits' => $event->result->calculatedKits,
            'skipped_kits' => $event->result->skippedKits,
            'failed_kits' => $event->result->failedKits,
            'failures_report_path' => $reportPath,
            'failures_report_disk' => $reportDisk,
        ]);
    }
}
