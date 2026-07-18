<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Manufacturer;

use App\Modules\Vehicles\Features\Import\Domain\DTOs\Manufacturer\ManufacturerCommandRowDTO;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;

interface UpsertManufacturerFromRowServiceInterface
{
    public function upsertFromRow(ManufacturerCommandRowDTO $row): ManufacturerData;
}
