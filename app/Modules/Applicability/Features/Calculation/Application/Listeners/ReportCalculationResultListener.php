<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Application\Listeners;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Notifications\CalculationNotificationServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting\CalculationFailureReporterInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\CalculationCompletionStatusEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use Psr\Log\LoggerInterface;

/**
 * Пишет отчет об ошибках после завершения расчета применяемости.
 */
final readonly class ReportCalculationResultListener
{
    /**
     * Получает порты отчета, notification и external context расчета.
     *
     * Шаги:
     * 1. Сохраняет reporter, который создает файл ошибок расчета.
     * 2. Сохраняет notifier результата расчета для внешнего контура.
     * 3. Сохраняет context service, который восстанавливает user id по operation id.
     * 4. Сохраняет logger для warning при расчетах с failures.
     */
    public function __construct(
        private CalculationFailureReporterInterface $reporter,
        private CalculationNotificationServiceInterface $notifications,
        private ExternalCalculationContextServiceInterface $context,
        private LoggerInterface $logger,
    ) {}

    /**
     * Сохраняет CSV с ошибками расчета и логирует только запуски с ошибками.
     *
     * Шаги:
     * 1. Передает aggregate result reporter-у и получает path отчета ошибок или `null`.
     * 2. Собирает notification DTO со счетчиками processed/calculated/skipped/failed.
     * 3. Восстанавливает user id из external context по operation id.
     * 4. Публикует completed или completed_with_failures notification.
     * 5. Если failures есть, пишет warning с operation id, счетчиками и путем отчета.
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
            userId: $this->context->pullUserId($event->operationId),
        ));

        if ($reportPath === null) {
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
