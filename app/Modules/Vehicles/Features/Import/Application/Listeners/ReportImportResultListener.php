<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Domain\Events\AbstractImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\EngineModification\EngineModificationImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Modification\ModificationImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleImportCompleted;
use LogicException;

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
        $this->service->report(
            userId: $event->userId,
            cacheKey: $event->cacheKey,
            importType: $this->importTypeFor($event),
            operationId: $event->operationId,
        );
    }

    /**
     * Определяет wire import_type по конкретному событию завершения.
     */
    private function importTypeFor(AbstractImportCompleted $event): ExternalImportTypeEnum
    {
        return match (true) {
            $event instanceof VehicleImportCompleted => ExternalImportTypeEnum::VehicleMultiSheet,
            $event instanceof EngineImportCompleted => ExternalImportTypeEnum::EngineMultiSheet,
            $event instanceof EngineCrossImportCompleted => ExternalImportTypeEnum::EngineCross,
            $event instanceof ManufacturerImportCompleted => ExternalImportTypeEnum::Manufacturer,
            $event instanceof ModificationImportCompleted => ExternalImportTypeEnum::ModificationCatalog,
            $event instanceof EngineModificationImportCompleted => ExternalImportTypeEnum::EngineModifications,
            default => throw new LogicException(sprintf('Unknown Vehicles import completion event [%s].', $event::class)),
        };
    }
}
