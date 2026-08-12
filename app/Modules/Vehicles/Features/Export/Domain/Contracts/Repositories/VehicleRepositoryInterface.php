<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Enums\VehicleExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

/**
 * Read port данных автомобилей для Excel export.
 */
interface VehicleRepositoryInterface
{
    /**
     * Для листа экспорта автомобилей.
     *
     * Шаги:
     * 1) Выбрать read projection по enum листа.
     * 2) Применить фильтр только разрешенных автомобилей, если он включен.
     * 3) Вернуть typed vehicle snapshots без Eloquent наружу.
     *
     * @return Collection<int, VehicleData>
     */
    public function forSheet(VehicleExportSheetEnum $sheet, bool $onlyAllowed): Collection;
}
