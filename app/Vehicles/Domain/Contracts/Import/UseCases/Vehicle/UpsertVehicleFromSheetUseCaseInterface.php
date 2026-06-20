<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\UseCases\Vehicle;

use App\Vehicles\Domain\Models\Vehicle;

interface UpsertVehicleFromSheetUseCaseInterface
{
    public function execute(array $row): Vehicle;
}
