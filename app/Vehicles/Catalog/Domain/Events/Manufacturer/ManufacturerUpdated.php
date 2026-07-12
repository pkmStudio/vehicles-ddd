<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Manufacturer;

use App\Vehicles\Catalog\Domain\ModelData\ManufacturerData;

final readonly class ManufacturerUpdated
{
    public function __construct(public int $userId, public string $operationId, public ManufacturerData $manufacturer) {}
}
