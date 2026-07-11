<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Manufacturer;

use App\Vehicles\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Vehicles\Import\Domain\ModelData\ManufacturerData;

interface UpsertManufacturerFromRowServiceInterface
{
    public function upsertFromRow(ManufacturerCommandRowDTO $row): ManufacturerData;
}
