<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements TemplatesClientInterface
{
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    public function buildVehicleDetails(DetailTemplateEnum $template, array $row, int $startIndex): array
    {
        return $this->templates->buildVehicleDetails($template->value, $row, $startIndex);
    }

    public function splitVehicleWiperDetails(array $details): array
    {
        return $this->templates->splitVehicleWiperDetails($details);
    }

    public function detectVehicleWiperSide(array $details): ?string
    {
        return $this->templates->detectVehicleWiperSide($details);
    }

    public function vehicleWiperSideData(array $details, string $side): array
    {
        return $this->templates->vehicleWiperSideData($details, $side);
    }
}
