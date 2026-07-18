<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle\VehicleWiperSheetRowDTO;

interface VehicleWiperSpecificationImportServiceInterface
{
    public function upsertFromRow(VehicleWiperSheetRowDTO $row): void;
}
