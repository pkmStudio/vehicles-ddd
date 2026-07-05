<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Application\Services;

use App\Vehicles\Import\Domain\Contracts\Services\VehicleImportServiceInterface;
use App\Vehicles\Import\Domain\DTOs\VehicleImportPlan;

final readonly class VehicleImportService implements VehicleImportServiceInterface
{
    public function buildImportPlan(bool $withWipers = true): VehicleImportPlan
    {
        return $withWipers ? VehicleImportPlan::all() : VehicleImportPlan::mainOnly();
    }
}
