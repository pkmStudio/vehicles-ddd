<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\TemplatesClientInterface as LocalTemplatesClientInterface;
use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements LocalTemplatesClientInterface
{
    public function __construct(
        private TemplatesClientInterface $templates,
    ) {}

    public function detectVehicleWiperSide(array $details): ?string
    {
        return $this->templates->detectVehicleWiperSide($details);
    }

    public function vehicleWiperSideData(array $details, string $side): array
    {
        return $this->templates->vehicleWiperSideData($details, $side);
    }
}
