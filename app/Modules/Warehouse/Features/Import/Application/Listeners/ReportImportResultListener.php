<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Application\Listeners;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Warehouse\Features\Import\Domain\Events\AbstractImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Events\KitImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Events\NomenclatureImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Events\PackDimensionImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Exceptions\ImportCompletionException;

/**
 * Реакция на завершение Warehouse-импорта: выгружает накопленные ошибки в отчёт и публикует
 * итоговый статус наружу. Один листенер на все родственные события (Nomenclature/PackDimension/
 * Kit) — реакция одна и та же, различается только выбор ImportTypeEnum для payload
 * (см. ARCHITECTURE.md §2).
 */
final readonly class ReportImportResultListener
{
    /**
     * Получает reporter ошибок импорта и notifier завершения.
     *
     * Шаги:
     * 1) Принять порт сохранения отчёта об ошибках.
     * 2) Принять хранилище накопленных validation failures.
     * 3) Принять порт публикации итогового уведомления.
     */
    public function __construct(
        private ImportFailureReporterInterface $reporter,
        private ImportFailureStoreInterface $failures,
        private ImportNotificationServiceInterface $notifier,
    ) {}

    /**
     * Этот метод собирает и публикует итог прогона импорта.
     *
     * Шаги:
     * 1) Забрать накопленные failures из cache по ключу события и очистить cache.
     * 2) Сохранить отчёт об ошибках в файл, если ошибки были.
     * 3) Определить статус (Completed без ошибок, Failed при наличии хотя бы одной) и тип каталога.
     * 4) Опубликовать уведомление о завершении.
     */
    public function handle(AbstractImportCompleted $event): void
    {
        $failures = $this->failures->pull($event->cacheKey);
        $importType = $this->importTypeFor($event);

        $reportPath = $this->reporter->store($failures, $importType);

        $notification = new ImportCompletionNotificationDTO(
            status: $failures === [] ? ImportCompletionStatusEnum::Completed : ImportCompletionStatusEnum::Failed,
            importType: $importType,
            userId: $event->userId,
            operationId: $event->operationId,
            failuresReportPath: $reportPath,
            failuresReportDisk: $reportPath === null ? null : (string) config(
                key: 'warehouse.import.failures.disk',
                default: 'local',
            ),
        );

        $this->notifier->notifyImportCompleted($notification);
    }

    /**
     * Определяет тип импорта по конкретному событию завершения.
     */
    private function importTypeFor(AbstractImportCompleted $event): ImportTypeEnum
    {
        return match (true) {
            $event instanceof NomenclatureImportCompleted => ImportTypeEnum::Nomenclature,
            $event instanceof PackDimensionImportCompleted => ImportTypeEnum::PackDimension,
            $event instanceof KitImportCompleted => ImportTypeEnum::Kit,
            default => throw ImportCompletionException::unknownEvent($event::class),
        };
    }
}
