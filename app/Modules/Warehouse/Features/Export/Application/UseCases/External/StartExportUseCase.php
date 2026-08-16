<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Application\UseCases\External;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Domain\Enums\ExportCompletionStatusEnum;
use Throwable;

/**
 * Запускает Warehouse-экспорт по внешнему запросу и отправляет итоговое уведомление.
 */
final readonly class StartExportUseCase
{
    /**
     * Получает сервис идемпотентности, фабрику экспортов и notifier.
     *
     * Шаги:
     * 1) Принять cache-порт для защиты operationId от повторного запуска.
     * 2) Принять фабрику, которая выбирает concrete Excel-export.
     * 3) Принять notifier для публикации успешного или неуспешного результата.
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
     * 1) Проверить operationId через cache, чтобы повтор брокера не запустил экспорт второй раз.
     * 2) Собрать контекст и выбрать Excel-адаптер по типу запроса.
     * 3) На ошибке снять cache-флаг, отправить failed-уведомление и пробросить исключение.
     * 4) После успешной записи файла отправить completed-уведомление с путём файла.
     */
    public function execute(ExportFileRequestDTO $request): void
    {
        $runAccepted = $this->cache->accept($request->operationId);

        if (! $runAccepted) {
            return;
        }

        try {
            $context = new ExportRunContextDTO(
                userId: $request->userId,
                operationId: $request->operationId,
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
            $this->cache->forgetAccepted($request->operationId);

            $failedNotification = new ExportCompletionNotificationDTO(
                userId: $request->userId,
                status: ExportCompletionStatusEnum::Failed,
                exportType: $request->exportType,
                operationId: $request->operationId,
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
            operationId: $request->operationId,
            disk: $request->disk,
            path: $path,
            typeId: $request->typeId,
        );
        $this->notifier->notifyExportCompleted($completedNotification);
    }
}
