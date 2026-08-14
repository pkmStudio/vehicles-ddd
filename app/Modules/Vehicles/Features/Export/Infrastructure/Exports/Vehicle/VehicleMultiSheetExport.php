<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Vehicle;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Exports\VehicleMultiSheetExportInterface;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\VehicleExportServiceInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\AbstractMultiSheetExport;
use App\Modules\Vehicles\Features\Export\Infrastructure\Exports\ReferenceSheetExport;

/**
 * Laravel Excel adapter multi-sheet export-а автомобилей.
 */
final readonly class VehicleMultiSheetExport extends AbstractMultiSheetExport implements VehicleMultiSheetExportInterface
{
    /**
     * Инициализирует фильтр export-а только разрешенных автомобилей.
     *
     * Шаги:
     * 1) Сохранить флаг `isAllow`, который будет передан vehicle sheet adapters.
     */
    public function __construct(
        private bool $isAllow = false,
    ) {}

    /**
     * Возвращает тип export-а автомобилей.
     *
     * Шаги:
     * 1) Вернуть `ExportTypeEnum::Vehicle` для имени output artifact.
     */
    protected function exportType(): ExportTypeEnum
    {
        return ExportTypeEnum::Vehicle;
    }

    /**
     * Собирает sheet adapters workbook-а автомобилей.
     *
     * Шаги:
     * 1) Получить export service из контейнера для справочного листа.
     * 2) Добавить основной лист автомобилей с флагом `isAllow`.
     * 3) Добавить лист дворников с флагом `isAllow`.
     * 4) Добавить reference sheet с headings/rows из export service.
     *
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $exportService = app(VehicleExportServiceInterface::class);

        return [
            app()->makeWith(VehicleMainSheetExport::class, ['isAllow' => $this->isAllow]),
            app()->makeWith(VehicleWipersSheetExport::class, ['isAllow' => $this->isAllow]),
            new ReferenceSheetExport(
                headings: $exportService->getReferenceHeadings(),
                rows: $exportService->getReferenceRows(),
            ),
        ];
    }
}
