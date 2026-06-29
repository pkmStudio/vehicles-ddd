<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Manufacturer;

use App\Vehicles\Domain\Models\Manufacturer;

interface UpsertManufacturerFromRowServiceInterface
{
    public function execute(array $row): Manufacturer;
}
