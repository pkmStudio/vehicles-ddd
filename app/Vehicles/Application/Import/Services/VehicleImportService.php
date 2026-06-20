<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Services;

use App\Vehicles\Domain\Contracts\Application\Import\Services\VehicleImportServiceInterface;
use App\Vehicles\Domain\DTOs\VehicleImportPlan;

final readonly class VehicleImportService implements VehicleImportServiceInterface
{
    public function buildImportPlan(bool $withWipers = true): VehicleImportPlan
    {
        return $withWipers ? VehicleImportPlan::all() : VehicleImportPlan::mainOnly();
    }
}
