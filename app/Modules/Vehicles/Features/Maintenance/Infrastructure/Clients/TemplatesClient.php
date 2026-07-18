<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Infrastructure\Clients;

use App\Modules\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Modules\Vehicles\Features\Maintenance\Domain\Contracts\Clients\TemplatesClientInterface;

final readonly class TemplatesClient implements TemplatesClientInterface
{
    public function __construct(
        private TemplatesPublicClientInterface $templates,
    ) {}

    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array
    {
        return $this->templates->splitVehicleWiperSpecification($details, $partSpecificationId);
    }
}
