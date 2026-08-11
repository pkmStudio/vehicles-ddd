<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Manufacturer;

final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function findByName(string $name): ?ManufacturerData
    {
        return $this->findByColumn('name', $name);
    }

    public function findByMfaId(int $mfaId): ?ManufacturerData
    {
        return $this->findByColumn('mfa_id', $mfaId);
    }

    public function findMinMfaId(): ?ManufacturerData
    {
        return ManufacturerData::optional(
            Manufacturer::query()
                ->orderBy('mfa_id')
                ->first(),
        );
    }

    private function findByColumn(string $column, int|string $value): ?ManufacturerData
    {
        return ManufacturerData::optional(
            Manufacturer::query()
                ->where($column, $value)
                ->first(),
        );
    }
}
