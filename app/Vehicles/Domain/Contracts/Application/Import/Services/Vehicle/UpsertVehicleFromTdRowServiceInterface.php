<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Vehicle;

use App\Vehicles\Domain\Models\Vehicle;

interface UpsertVehicleFromTdRowServiceInterface
{
    public function upsertFromRow(array $row): ?Vehicle;
}
