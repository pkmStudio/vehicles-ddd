<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Import\UseCases\Manufacturer;

use App\Vehicles\Domain\Models\Manufacturer;

interface UpsertManufacturerFromRowUseCaseInterface
{
    public function execute(array $row): Manufacturer;
}
