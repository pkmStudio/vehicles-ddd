<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients;

interface TemplatesClientInterface
{
    /** @param array<string, mixed> $details */
    public function detectVehicleWiperSide(array $details): ?string;

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    public function vehicleWiperSideData(array $details, string $side): array;
}
