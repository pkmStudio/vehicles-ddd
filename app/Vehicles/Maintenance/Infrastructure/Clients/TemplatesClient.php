<?php

declare(strict_types=1);

namespace App\Vehicles\Maintenance\Infrastructure\Clients;

use App\Templates\Domain\Contracts\Clients\TemplatesClientInterface as TemplatesPublicClientInterface;
use App\Vehicles\Maintenance\Domain\Contracts\Clients\TemplatesClientInterface;

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
