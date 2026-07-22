<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Reporting;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Завершение импорта: если были ошибки — формируем отчёт и отправляем статус в
 * внешний сервис, иначе фиксируем успешное завершение. Кэш ошибок очищается в любом случае.
 */
final readonly class ReportImportResultService implements ReportImportResultServiceInterface
{
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private ImportFailureStoreInterface $failureStore,
        private FileNotificationServiceInterface $notifier,
        private LoggerInterface $logger,
    ) {}

    public function report(int $userId, string $cacheKey, ?string $runId = null): void
    {
        $failures = $this->failureStore->get($cacheKey);
        $errorsCount = count($failures);

        try {
            $path = $this->reporter->store($failures);

            if ($errorsCount > 0) {
                $reportPath = is_string($path) ? $path : null;

                $notification = new ImportCompletionNotificationDTO(
                    userId: $userId,
                    status: ImportCompletionStatusEnum::CompletedWithErrors,
                    runId: $runId,
                    errorsCount: $errorsCount,
                    path: $reportPath,
                );
                $this->notifier->notifyImportCompleted($notification);
            } else {
                $notification = new ImportCompletionNotificationDTO(
                    userId: $userId,
                    status: ImportCompletionStatusEnum::Completed,
                    runId: $runId,
                );
                $this->notifier->notifyImportCompleted($notification);
            }
        } catch (Throwable $e) {
            $this->logger->error('Import reporting failed', ['exception' => $e]);

            $failedNotification = new ImportCompletionNotificationDTO(
                userId: $userId,
                status: ImportCompletionStatusEnum::Failed,
                runId: $runId,
                errorsCount: $errorsCount,
            );
            $this->notifier->notifyImportCompleted($failedNotification);
        } finally {
            $this->failureStore->forget($cacheKey);
        }
    }
}
