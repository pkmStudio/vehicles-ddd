<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Shared\Domain\Contracts\Clients;

use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;

interface WarehouseApplicabilityClientInterface
{
    /**
     * @return iterable<int, WarehouseKitForApplicabilityDTO>
     */
    public function activeApplicabilityKits(?int $kitId = null, int $chunk = 1000): iterable;

    public function kitExists(int $kitId): bool;
}
