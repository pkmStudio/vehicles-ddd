<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperLengthDTO;
use App\Modules\Applicability\Features\Calculation\Domain\ModelData\VehiclePartSpecificationData;
use Illuminate\Support\Collection;

interface VehiclesApplicabilityClientInterface
{
    /** @return Collection<int, VehiclePartSpecificationData> */
    public function frontWiperSpecifications(WiperLengthDTO $length): Collection;

    /** @return Collection<int, VehiclePartSpecificationData> */
    public function rearWiperSpecifications(WiperLengthDTO $length): Collection;
}
