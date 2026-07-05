<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Vehicle;

interface VehicleWiperSpecificationImportServiceInterface
{
    public function execute(int $vehicleId, string $templateSlug, array $details, ?string $featureValueName = null, ?string $name = null, ?string $text = null): void;
}
