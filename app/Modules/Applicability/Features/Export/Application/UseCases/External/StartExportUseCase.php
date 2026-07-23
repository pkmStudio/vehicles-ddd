<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Application\UseCases\External;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use Throwable;

final readonly class StartExportUseCase implements StartExportUseCaseInterface
{
    public function __construct(
        private ExportRunCacheServiceInterface $cache,
        private ExportFileFactoryInterface $exportFactory,
        private ExportNotificationServiceInterface $notifier,
    ) {}

    public function execute(ExportFileRequestDTO $request): void
    {
        if (! $this->cache->accept($request->runId)) {
            return;
        }

        try {
            $context = new ExportRunContextDTO(
                userId: $request->userId,
                runId: $request->runId,
            );
            $path = $this->exportFactory
                ->make($request->exportType)
                ->export($context, $request->disk);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->runId);
            $this->notifier->notifyExportCompleted(new ExportCompletionNotificationDTO(
                userId: $request->userId,
                status: ExportCompletionStatusEnum::Failed,
                exportType: $request->exportType,
                runId: $request->runId,
                disk: $request->disk,
            ));

            throw $e;
        }

        $this->notifier->notifyExportCompleted(new ExportCompletionNotificationDTO(
            userId: $request->userId,
            status: ExportCompletionStatusEnum::Completed,
            exportType: $request->exportType,
            runId: $request->runId,
            disk: $request->disk,
            path: $path,
        ));
    }
}
