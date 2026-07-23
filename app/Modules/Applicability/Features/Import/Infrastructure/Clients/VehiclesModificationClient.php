<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Clients;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\VehiclesModificationClientInterface;
use App\Modules\Vehicles\Shared\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;

/**
 * Адаптер публичного Vehicles API к import-порту Applicability.
 */
final readonly class VehiclesModificationClient implements VehiclesModificationClientInterface
{
    public function __construct(
        private VehiclesApplicabilityClientInterface $vehicles,
    ) {}

    public function resolveByMsAndModId(int $msId, int $modId): int
    {
        return $this->vehicles->resolveModificationIdByMsAndModId($msId, $modId);
    }
}
