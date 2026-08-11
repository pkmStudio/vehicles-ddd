<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability;

use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;

interface WarehouseApplicabilityRepositoryInterface
{
    /**
     * @return iterable<int, WarehouseKitForApplicabilityDTO>
     */
    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable;

    public function kitExists(int $kitId): bool;
}
