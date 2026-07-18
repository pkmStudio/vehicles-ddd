<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\Contracts\Services\Expanders;

use App\Modules\Vehicles\Features\Export\Domain\DTOs\WiperExportRowDTO;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

interface WiperRowExpanderInterface
{
    /**
     * @param  Collection<int, VehicleData>  $vehicles
     * @return Collection<int, WiperExportRowDTO>
     */
    public function expand(Collection $vehicles): Collection;
}
