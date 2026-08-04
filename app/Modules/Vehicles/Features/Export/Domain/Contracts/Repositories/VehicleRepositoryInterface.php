<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Enums\VehicleExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

interface VehicleRepositoryInterface
{
    /**
     * Для листа экспорта автомобилей.
     *
     * @return Collection<int, VehicleData>
     */
    public function forSheet(VehicleExportSheetEnum $sheet, bool $onlyAllowed): Collection;
}
