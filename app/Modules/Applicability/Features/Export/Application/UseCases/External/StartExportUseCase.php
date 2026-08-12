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
    /**
     * Принимает порты, нужные для запуска внешнего export workflow.
     *
     * Шаги:
     * 1. Сохраняет cache guard для защиты от повторного запуска по operation id.
     * 2. Сохраняет фабрику, выбирающую конкретный Excel export adapter по типу экспорта.
     * 3. Сохраняет notifier, который публикует итоговый результат во внешний контур.
     */
    public function __construct(
        private ExportRunCacheServiceInterface $cache,
        private ExportFileFactoryInterface $exportFactory,
        private ExportNotificationServiceInterface $notifier,
    ) {}

    /**
     * Запускает внешний экспорт применяемости и публикует финальный статус.
     *
     * Шаги:
     * 1. Проверяет operation id в cache guard и молча завершает дубликат.
     * 2. Собирает run context из user id и operation id входящего запроса.
     * 3. Выбирает export adapter через фабрику и сохраняет XLSX на целевой disk.
     * 4. При ошибке освобождает guard, публикует failed notification и пробрасывает исключение.
     * 5. При успехе публикует completed notification с путем к созданному файлу.
     */
    public function execute(ExportFileRequestDTO $request): void
    {
        if (! $this->cache->accept($request->operationId)) {
            return;
        }

        try {
            $context = new ExportRunContextDTO(
                userId: $request->userId,
                operationId: $request->operationId,
            );
            $path = $this->exportFactory
                ->make($request->exportType)
                ->export($context, $request->disk);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->operationId);
            $this->notifier->notifyExportCompleted(new ExportCompletionNotificationDTO(
                userId: $request->userId,
                status: ExportCompletionStatusEnum::Failed,
                exportType: $request->exportType,
                operationId: $request->operationId,
                disk: $request->disk,
            ));

            throw $e;
        }

        $this->notifier->notifyExportCompleted(new ExportCompletionNotificationDTO(
            userId: $request->userId,
            status: ExportCompletionStatusEnum::Completed,
            exportType: $request->exportType,
            operationId: $request->operationId,
            disk: $request->disk,
            path: $path,
        ));
    }
}
