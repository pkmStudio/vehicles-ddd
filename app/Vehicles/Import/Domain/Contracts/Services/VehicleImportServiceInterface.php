<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services;

use App\Vehicles\Import\Domain\DTOs\VehicleImportPlan;

interface VehicleImportServiceInterface
{
    public function buildImportPlan(bool $withWipers = true): VehicleImportPlan;
}
