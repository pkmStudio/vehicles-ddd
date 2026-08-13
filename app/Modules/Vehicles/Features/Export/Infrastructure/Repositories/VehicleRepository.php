<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\VehicleExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Читает автомобили и связанные specification records для Excel-экспорта Vehicles.
 */
final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Возвращает данные автомобилей для конкретного export-листа.
     *
     * Шаги:
     * 1) Сопоставить enum листа с приватным query method.
     * 2) Передать флаг фильтрации разрешенных автомобилей в выбранный query method.
     * 3) Вернуть коллекцию typed `VehicleData`.
     */
    public function forSheet(VehicleExportSheetEnum $sheet, bool $onlyAllowed): Collection
    {
        return match ($sheet) {
            VehicleExportSheetEnum::Main => $this->mainSheet($onlyAllowed),
            VehicleExportSheetEnum::Wiper => $this->wiperSheet($onlyAllowed),
        };
    }

    /**
     * Возвращает данные для основного листа автомобилей.
     *
     * Шаги:
     * 1) Подготовить optional scope `is_allow = true`.
     * 2) Прочитать автомобили с manufacturer и parent relations.
     * 3) Сконвертировать Eloquent collection в Support Collection typed `VehicleData`.
     */
    private function mainSheet(bool $onlyAllowed): Collection
    {
        $onlyAllowedFilter = fn ($query) => $query->where('is_allow', true);

        $vehicles = Vehicle::query()
            ->with(['manufacturer', 'parent'])
            ->when($onlyAllowed, $onlyAllowedFilter)
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }

    /**
     * Возвращает автомобили со спецификациями дворников.
     *
     * Шаги:
     * 1) Подготовить scope для загрузки только `WIPER` part specifications с feature value.
     * 2) Подготовить optional scope `is_allow = true`.
     * 3) Прочитать автомобили с manufacturer, parent и отфильтрованными specifications.
     * 4) Сконвертировать Eloquent collection в Support Collection typed `VehicleData`.
     */
    private function wiperSheet(bool $onlyAllowed): Collection
    {
        $wiperSpecifications = fn ($query) => $query
            ->where('template', DetailTemplateEnum::WIPER)
            ->with('featureValue');
        $onlyAllowedFilter = fn ($query) => $query->where('is_allow', true);

        $vehicles = Vehicle::query()
            ->with([
                'manufacturer',
                'parent',
                'partSpecifications' => $wiperSpecifications,
            ])
            ->when($onlyAllowed, $onlyAllowedFilter)
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }
}
