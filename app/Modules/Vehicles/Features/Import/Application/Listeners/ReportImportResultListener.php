<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\AbstractImportCompleted;

/**
 * Реагирует на завершение import-сценария и публикует result notification.
 */
final readonly class ReportImportResultListener
{
    /**
     * Инициализирует service публикации результата импорта.
     *
     * Шаги:
     * 1) Сохранить application service, который собирает и отправляет import result.
     */
    public function __construct(
        private ReportImportResultServiceInterface $service,
    ) {}

    /**
     * Обрабатывает событие завершения импорта.
     *
     * Шаги:
     * 1) Взять user id, cache key и operation id из import event.
     * 2) Делегировать сборку отчета и отправку notification application service-у.
     */
    public function handle(AbstractImportCompleted $event): void
    {
        $this->service->report($event->userId, $event->cacheKey, $event->operationId);
    }
}
