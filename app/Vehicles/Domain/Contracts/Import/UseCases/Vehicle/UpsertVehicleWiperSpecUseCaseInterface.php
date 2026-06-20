<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\UseCases\Vehicle;

interface UpsertVehicleWiperSpecUseCaseInterface
{
    public function execute(int $vehicleId, string $templateSlug, array $details, ?string $featureValueName = null, ?string $name = null, ?string $text = null): void;
}
