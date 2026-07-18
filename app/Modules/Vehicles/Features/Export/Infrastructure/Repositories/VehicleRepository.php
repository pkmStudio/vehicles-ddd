<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Vehicle;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use Illuminate\Support\Collection;

final readonly class VehicleRepository implements VehicleRepositoryInterface
{
    public function forMainSheet(bool $onlyAllowed): Collection
    {
        $vehicles = Vehicle::query()
            ->with(['manufacturer', 'parent'])
            ->when($onlyAllowed, fn ($q) => $q->where('is_allow', true))
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }

    public function forWiperSheet(bool $onlyAllowed): Collection
    {
        $vehicles = Vehicle::query()
            ->with([
                'manufacturer',
                'parent',
                'partSpecifications' => fn ($q) => $q
                    ->where('template', DetailTemplateEnum::WIPER)
                    ->with('featureValue'),
            ])
            ->when($onlyAllowed, fn ($q) => $q->where('is_allow', true))
            ->get();

        return VehicleData::collect($vehicles, Collection::class);
    }
}
