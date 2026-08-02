<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\UseCases\Vehicle;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;

/**
 * Use case port списка и options CRM API Vehicles.
 */
interface ListVehiclesForCrmUseCaseInterface
{
    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function execute(VehicleCrmReadQueryDTO $query): array;

    /**
     * @return list<array{id: int, label: string}>
     */
    public function features(): array;

    /**
     * @return list<array{id: int, feature_id: int, label: string, short_code: string}>
     */
    public function featureValues(int $featureId): array;

    /**
     * @return list<array{id: string, label: string}>
     */
    public function detailTemplates(): array;
}
