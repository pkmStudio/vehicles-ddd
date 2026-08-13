<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Application\Listeners;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Applicability\Features\Import\Domain\Events\AbstractImportCompleted;

final readonly class ReportImportResultListener
{
    /**
     * Получает порты сборки отчета и публикации результата импорта.
     *
     * Шаги:
     * 1. Сохраняет reporter, который создает XLSX-файл ошибок.
     * 2. Сохраняет transient store накопленных row failures.
     * 3. Сохраняет notifier completion-события для внешнего контура.
     */
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private ImportFailureStoreInterface $failures,
        private ImportNotificationServiceInterface $notifier,
    ) {}

    /**
     * Формирует отчет ошибок и публикует итоговый статус import workflow.
     *
     * Шаги:
     * 1. Забирает накопленные ошибки строк по cache key завершенного import-а.
     * 2. Сохраняет XLSX-отчет, если ошибки есть.
     * 3. Выбирает status `completed` при пустом списке ошибок и `failed` при наличии failures.
     * 4. Публикует notification с operation id, user id и ссылкой на отчет ошибок.
     */
    public function handle(AbstractImportCompleted $event): void
    {
        $failures = $this->failures->pull($event->cacheKey);
        $reportPath = $this->reporter->store($failures);

        $this->notifier->notifyImportCompleted(new ImportCompletionNotificationDTO(
            status: $failures === [] ? ImportCompletionStatusEnum::Completed : ImportCompletionStatusEnum::Failed,
            importType: ImportTypeEnum::KitApplicability,
            userId: $event->userId,
            operationId: $event->operationId,
            failuresReportPath: $reportPath,
            failuresReportDisk: $reportPath === null ? null : (string) config('applicability.import.failures.disk', 'local'),
        ));
    }
}
