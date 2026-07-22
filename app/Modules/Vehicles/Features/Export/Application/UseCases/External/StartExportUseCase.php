<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Application\UseCases\External;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Vehicles\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use Throwable;

/**
 * Принимает RabbitMQ-команду на экспорт каталога и запускает соответствующий
 * Excel-адаптер. Симметрично Import\Application\UseCases\External\
 * StartExternalFileImportUseCase.
 */
final readonly class StartExportUseCase implements StartExportUseCaseInterface
{
    /**
     * Инициализирует зависимости сценария внешнего запуска экспорта.
     */
    public function __construct(
        private ExportRunCacheServiceInterface $cache,
        private ExportFileFactoryInterface $exportFactory,
        private ExportNotificationServiceInterface $notifier,
    ) {}

    /**
     * Обеспечивает идемпотентность runId, запускает выбранный экспорт и уведомляет
     * инициатора о готовности файла.
     *
     * Шаги:
     * 1. Просит cache-сервис принять runId; повторный запрос не запускает экспорт дважды.
     * 2. Выбирает Excel-адаптер через фабрику и синхронно строит файл на указанном disk
     *    (RabbitMQ-консьюмер — уже асинхронная граница, отдельная очередь не нужна).
     * 3. При ошибке снимает отметку принятого runId (чтобы повторная доставка сообщения
     *    могла попробовать снова), публикует FILE_EXPORTED со статусом Failed — инициатор не
     *    должен узнавать о сбое только по факту отсутствия ответа/по retry-таймауту брокера —
     *    и пробрасывает ошибку дальше (retry/DLQ — уже на стороне rabbit-transport, см.
     *    config/rabbit-transport.php:consumer).
     * 4. При успехе публикует FILE_EXPORTED со статусом Completed и путём к файлу.
     */
    public function execute(ExportFileRequestDTO $request): void
    {
        $isAccepted = $this->cache->accept($request->runId);
        if (! $isAccepted) {
            return;
        }

        try {
            $context = new ExportRunContextDTO(userId: $request->userId, runId: $request->runId);
            $export = $this->exportFactory->make($request->exportType, $request->isAllow);
            $path = $export->export($context, $request->disk);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->runId);

            $failedNotification = new ExportCompletionNotificationDTO(
                userId: $request->userId,
                status: ExportCompletionStatusEnum::Failed,
                exportType: $request->exportType,
                runId: $request->runId,
                disk: $request->disk,
            );
            $this->notifier->notifyExportCompleted($failedNotification);

            throw $e;
        }

        $completedNotification = new ExportCompletionNotificationDTO(
            userId: $request->userId,
            status: ExportCompletionStatusEnum::Completed,
            exportType: $request->exportType,
            runId: $request->runId,
            disk: $request->disk,
            path: $path,
        );
        $this->notifier->notifyExportCompleted($completedNotification);
    }
}
