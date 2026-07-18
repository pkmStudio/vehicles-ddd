<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Maintenance\Domain\Contracts\Clients;

interface TemplatesClientInterface
{
    /**
     * @param  array<string, mixed>  $details
     * @return array<int, array<string, mixed>>
     */
    public function splitVehicleWiperSpecification(array $details, ?int $partSpecificationId): array;
}
