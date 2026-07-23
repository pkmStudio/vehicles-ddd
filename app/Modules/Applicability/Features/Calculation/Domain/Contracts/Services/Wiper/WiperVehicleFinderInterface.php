<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperAdaptersDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\WiperKitPositionEnum;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\VehiclePartSpecificationData;
use Illuminate\Support\Collection;

interface WiperVehicleFinderInterface
{
    /** @return Collection<int, VehiclePartSpecificationData> */
    public function find(WiperLengthDTO $wipers, WiperAdaptersDTO $adapters, WiperKitPositionEnum $position): Collection;
}
