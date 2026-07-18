<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Templates\Domain\Enums\DetailTemplateEnum;
use App\Modules\Vehicles\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements TemplatesClientInterface
{
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    public function vehicleDetailHeadings(DetailTemplateEnum $template): array
    {
        return $this->templates->vehicleDetailHeadings($template->value);
    }

    public function renderVehicleDetails(DetailTemplateEnum $template, array $details): array
    {
        return $this->templates->renderVehicleDetails($template->value, $details);
    }

    public function vehicleWiperSideData(array $details, string $side): array
    {
        return $this->templates->vehicleWiperSideData($details, $side);
    }

    public function mergeVehicleWiperForExport(array $front, array $back): array
    {
        return $this->templates->mergeVehicleWiperForExport($front, $back);
    }
}
