<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Application\Listeners;

use App\Warehouse\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Warehouse\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Warehouse\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Warehouse\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Warehouse\Import\Domain\Enums\ImportTypeEnum;
use App\Warehouse\Import\Domain\Events\AbstractImportCompleted;
use App\Warehouse\Import\Domain\Events\KitImportCompleted;
use App\Warehouse\Import\Domain\Events\NomenclatureImportCompleted;
use App\Warehouse\Import\Domain\Events\PackDimensionImportCompleted;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Реакция на завершение Warehouse-импорта: выгружает накопленные ошибки в отчёт и публикует
 * итоговый статус наружу. Один листенер на все родственные события (Nomenclature/PackDimension/
 * Kit) — реакция одна и та же, различается только выбор ImportTypeEnum для payload
 * (см. ARCHITECTURE.md §2).
 */
final readonly class ReportImportResultListener
{
    public function __construct(
        private ImportFailureReporterInterface $reporter,
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
        $failures = Cache::get($event->cacheKey, []);
        Cache::forget($event->cacheKey);

        $reportPath = $this->reporter->store($failures);

        $notification = new ImportCompletionNotificationDTO(
            status: $failures === [] ? ImportCompletionStatusEnum::Completed : ImportCompletionStatusEnum::Failed,
            importType: $this->importTypeFor($event),
            userId: $event->userId,
            runId: $event->runId,
            failuresReportPath: $reportPath,
            failuresReportDisk: $reportPath === null ? null : (string) config('warehouse.import.failures.disk', 'local'),
        );

        $this->notifier->notifyImportCompleted($notification);
    }

    private function importTypeFor(AbstractImportCompleted $event): ImportTypeEnum
    {
        return match (true) {
            $event instanceof NomenclatureImportCompleted => ImportTypeEnum::Nomenclature,
            $event instanceof PackDimensionImportCompleted => ImportTypeEnum::PackDimension,
            $event instanceof KitImportCompleted => ImportTypeEnum::Kit,
            default => throw new RuntimeException('Неизвестное событие завершения Warehouse-импорта: '.$event::class),
        };
    }
}
