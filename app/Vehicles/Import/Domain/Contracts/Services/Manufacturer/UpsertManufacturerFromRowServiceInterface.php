<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Manufacturer;

use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;

interface UpsertManufacturerFromRowServiceInterface
{
    public function upsertFromRow(array $row): ManufacturerData;
}
