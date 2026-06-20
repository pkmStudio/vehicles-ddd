<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services;

use App\Vehicles\Domain\DTOs\VehicleImportPlan;

interface VehicleImportServiceInterface
{
    public function buildImportPlan(bool $withWipers = true): VehicleImportPlan;
}
