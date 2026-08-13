<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Services\Reporting;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Завершение импорта: если были ошибки — формируем отчёт и отправляем статус в
 * внешний сервис, иначе фиксируем успешное завершение. Кэш ошибок очищается в любом случае.
 */
final readonly class ReportImportResultService implements ReportImportResultServiceInterface
{
    /**
     * Инициализирует порты формирования и отправки результата импорта.
     *
     * Шаги:
     * 1) Сохранить reporter для записи файла ошибок.
     * 2) Сохранить failure store для чтения и очистки cached row failures.
     * 3) Сохранить notifier для публикации import completion.
     * 4) Сохранить logger для actionable reporting failures.
     */
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private ImportFailureStoreInterface $failureStore,
        private FileNotificationServiceInterface $notifier,
        private LoggerInterface $logger,
    ) {}

    /**
     * Формирует отчет об ошибках и отправляет notification о завершении импорта.
     *
     * Шаги:
     * 1) Получить failures по cache key и посчитать количество ошибок.
     * 2) Попробовать сохранить report artifact через reporter.
     * 3) Если ошибки есть — отправить `CompletedWithErrors` с disk/path отчета.
     * 4) Если ошибок нет — отправить `Completed`.
     * 5) При сбое reporting workflow залогировать error и отправить `Failed`.
     * 6) В любом случае очистить cached failures.
     */
    public function report(
        int $userId,
        string $cacheKey,
        ExternalImportTypeEnum $importType,
        ?string $operationId = null,
    ): void {
        $failures = $this->failureStore->get($cacheKey);
        $errorsCount = count($failures);

        try {
            $path = $this->reporter->store($failures);
            $reportDisk = (string) config('vehicles.import.failures.disk', 'local');

            if ($errorsCount > 0) {
                $reportPath = is_string($path) ? $path : null;

                $notification = new ImportCompletionNotificationDTO(
                    userId: $userId,
                    status: ImportCompletionStatusEnum::CompletedWithErrors,
                    importType: $importType,
                    operationId: $operationId,
                    disk: $reportDisk,
                    errorsCount: $errorsCount,
                    path: $reportPath,
                );
                $this->notifier->notifyImportCompleted($notification);
            } else {
                $notification = new ImportCompletionNotificationDTO(
                    userId: $userId,
                    status: ImportCompletionStatusEnum::Completed,
                    importType: $importType,
                    operationId: $operationId,
                    disk: null,
                );
                $this->notifier->notifyImportCompleted($notification);
            }
        } catch (Throwable $e) {
            $this->logger->error('Import reporting failed', [
                'operation_id' => $operationId,
                'user_id' => $userId,
                'errors_count' => $errorsCount,
                'exception' => $e,
            ]);

            $failedNotification = new ImportCompletionNotificationDTO(
                userId: $userId,
                status: ImportCompletionStatusEnum::Failed,
                importType: $importType,
                operationId: $operationId,
                errorsCount: $errorsCount,
            );
            $this->notifier->notifyImportCompleted($failedNotification);
        } finally {
            $this->failureStore->forget($cacheKey);
        }
    }
}
