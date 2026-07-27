<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerSheetRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface UpsertManufacturerFromSheetServiceInterface
{
    public function upsertFromRow(ManufacturerSheetRowDTO $row): ManufacturerData;
}
