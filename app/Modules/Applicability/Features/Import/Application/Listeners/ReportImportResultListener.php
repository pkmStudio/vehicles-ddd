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
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private ImportFailureStoreInterface $failures,
        private ImportNotificationServiceInterface $notifier,
    ) {}

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
