<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\Contracts\Services\Expanders;

use App\Vehicles\Export\Domain\DTOs\WiperExportRowDTO;
use App\Vehicles\Export\Domain\ModelData\VehicleData;
use Illuminate\Support\Collection;

interface WiperRowExpanderInterface
{
    /**
     * @param  Collection<int, VehicleData>  $vehicles
     * @return Collection<int, WiperExportRowDTO>
     */
    public function expand(Collection $vehicles): Collection;
}
