<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Wiper\WiperVehicleSideDetailsDTO;

interface TemplatesClientInterface
{
    /** @param array<string, mixed> $details */
    public function detectVehicleWiperSide(array $details): ?string;

    public function vehicleWiperSideData(array $details, string $side): WiperVehicleSideDetailsDTO;
}
