<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services\Reporting;

use App\Vehicles\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Vehicles\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Vehicles\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Vehicles\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Vehicles\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Завершение импорта: если были ошибки — формируем отчёт и отправляем статус в
 * внешний сервис, иначе фиксируем успешное завершение. Кэш ошибок очищается в любом случае.
 */
final readonly class ReportImportResultService implements ReportImportResultServiceInterface
{
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private FileNotificationServiceInterface $notifier,
    ) {}

    public function report(int $userId, string $cacheKey, ?string $runId = null): void
    {
        $failures = Cache::get($cacheKey, []);
        $errorsCount = is_array($failures) ? count($failures) : 0;

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
            Log::error('Import reporting failed', ['exception' => $e]);

            $failedNotification = new ImportCompletionNotificationDTO(
                userId: $userId,
                status: ImportCompletionStatusEnum::Failed,
                runId: $runId,
                errorsCount: $errorsCount,
            );
            $this->notifier->notifyImportCompleted($failedNotification);
        } finally {
            Cache::forget($cacheKey);
        }
    }
}
