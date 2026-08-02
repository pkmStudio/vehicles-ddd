<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle;

/**
 * Use case port compact search CRM API Vehicles.
 */
interface SearchVehiclesForCrmUseCaseInterface
{
    /**
     * @return list<array{id: int, label: string, ms_id: int, manufacturer: ?string}>
     */
    public function execute(string $query, int $limit = 20): array;
}
