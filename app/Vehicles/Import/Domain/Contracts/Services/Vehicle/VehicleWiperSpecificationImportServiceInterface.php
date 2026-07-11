<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Vehicle;

use App\Vehicles\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;

interface VehicleWiperSpecificationImportServiceInterface
{
    public function upsertFromRow(VehicleWiperSheetRowDTO $row): void;
}
