<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Clients;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\Applicability\WarehouseApplicabilityRepositoryInterface;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;
use App\Modules\Warehouse\Shared\Domain\DTOs\Applicability\WarehouseKitForApplicabilityDTO;

final readonly class WarehouseApplicabilityClient implements WarehouseApplicabilityClientInterface
{
    public function __construct(
        private WarehouseApplicabilityRepositoryInterface $kits,
    ) {}

    /**
     * @return iterable<int, WarehouseKitForApplicabilityDTO>
     */
    public function activeApplicabilityKits(?int $kitId = null, int $chunk = 1000): iterable
    {
        return $this->kits->activeKits(
            kitId: $kitId,
            chunk: $chunk,
        );
    }

    public function kitExists(int $kitId): bool
    {
        return $this->kits->kitExists($kitId);
    }
}
