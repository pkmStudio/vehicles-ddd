<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\Events\Manufacturer;

final readonly class ManufacturerDeleted
{
    public function __construct(public int $userId, public string $operationId, public int $mfaId, public int $manufacturerId) {}
}
