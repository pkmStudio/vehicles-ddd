<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\Enums\VehicleExportSheetEnum;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Vehicle;
use Illuminate\Support\Collection;

final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    public function forSheet(VehicleExportSheetEnum $sheet, bool $onlyAllowed): Collection
    {
        return match ($sheet) {
            VehicleExportSheetEnum::Main => $this->mainSheet($onlyAllowed),
            VehicleExportSheetEnum::Wiper => $this->wiperSheet($onlyAllowed),
        };
    }

    private function mainSheet(bool $onlyAllowed): Collection
    {
        $onlyAllowedFilter = fn ($query) => $query->where('is_allow', true);

        $vehicles = Vehicle::query()
            ->with(['manufacturer', 'parent'])
            ->when($onlyAllowed, $onlyAllowedFilter)
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }

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
