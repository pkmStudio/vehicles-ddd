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
        return ManufacturerData::optional(Manufacturer::query()->where('name', $name)->first());
    }

    public function findByMfaId(int $mfaId): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->where('mfa_id', $mfaId)->first());
    }

    public function findMinMfaId(): ?ManufacturerData
    {
        return ManufacturerData::optional(
            Manufacturer::query()
                ->orderBy('mfa_id')
                ->first(),
        );
    }
}
