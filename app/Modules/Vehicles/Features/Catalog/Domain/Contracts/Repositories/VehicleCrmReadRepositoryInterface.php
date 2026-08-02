<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\VehicleCrmReadQueryDTO;

/**
 * Read-порт CRM API каталога Vehicles.
 */
interface VehicleCrmReadRepositoryInterface
{
    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function paginate(VehicleCrmReadQueryDTO $query): array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * @return list<array{id: int, label: string, ms_id: int, manufacturer: ?string}>
     */
    public function search(string $query, int $limit = 20): array;

    /**
     * @return list<array{id: int, label: string}>
     */
    public function featureOptions(): array;

    /**
     * @return list<array{id: int, feature_id: int, label: string, short_code: string}>
     */
    public function featureValueOptions(int $featureId): array;

    /**
     * @return list<array{id: string, label: string}>
     */
    public function detailTemplateOptions(): array;
}
