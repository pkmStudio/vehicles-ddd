<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle;

use App\Vehicles\Domain\Models\Vehicle;

interface UpsertVehicleFromTdRowUseCaseInterface
{
    public function execute(array $row): ?Vehicle;
}
