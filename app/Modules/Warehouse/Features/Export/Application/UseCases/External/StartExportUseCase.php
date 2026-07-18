<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Application\UseCases\External;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use Throwable;

/**
 * Запускает Warehouse-экспорт по внешнему запросу и отправляет итоговое уведомление.
 */
final readonly class StartExportUseCase implements StartExportUseCaseInterface
{
    /**
     * Получает сервис идемпотентности, фабрику экспортов и notifier.
     */
    public function __construct(
        private ExportRunCacheServiceInterface $cache,
        private ExportFileFactoryInterface $exportFactory,
        private ExportNotificationServiceInterface $notifier,
    ) {}

    /**
     * Принимает внешний запрос, создаёт файл экспорта и публикует статус завершения.
     *
     * Шаги:
     * 1) Проверить runId через cache, чтобы повтор брокера не запустил экспорт второй раз.
     * 2) Собрать контекст и выбрать Excel-адаптер по типу запроса.
     * 3) На ошибке снять cache-флаг, отправить failed-уведомление и пробросить исключение.
     * 4) После успешной записи файла отправить completed-уведомление с путём файла.
     */
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
            $export = $this->exportFactory->make(
                type: $request->exportType,
                typeId: $request->typeId,
                kitFilters: $request->kitFilters,
                kitSort: $request->kitSort,
            );
            $path = $export->export(
                context: $context,
                disk: $request->disk,
            );
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->runId);

            $failedNotification = new ExportCompletionNotificationDTO(
                userId: $request->userId,
                status: ExportCompletionStatusEnum::Failed,
                exportType: $request->exportType,
                runId: $request->runId,
                disk: $request->disk,
                typeId: $request->typeId,
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
            typeId: $request->typeId,
        );
        $this->notifier->notifyExportCompleted($completedNotification);
    }
}
