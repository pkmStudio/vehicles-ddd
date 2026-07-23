<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Clients;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Warehouse\Shared\Domain\Contracts\Clients\WarehouseApplicabilityClientInterface;

/**
 * Адаптер публичного Warehouse API к import-порту Applicability.
 */
final readonly class WarehouseKitClient implements WarehouseKitClientInterface
{
    public function __construct(
        private WarehouseApplicabilityClientInterface $warehouse,
    ) {}

    public function exists(int $kitId): bool
    {
        return $this->warehouse->kitExists($kitId);
    }
}
