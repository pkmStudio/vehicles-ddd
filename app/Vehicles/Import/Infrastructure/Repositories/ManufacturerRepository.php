<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\Manufacturer\ManufacturerData;
use App\Vehicles\Import\Infrastructure\Models\Manufacturer;
use Illuminate\Support\Collection;

final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function find(int $id): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->find($id));
    }

    public function findOrFail(int $id): ManufacturerData
    {
        return ManufacturerData::from(Manufacturer::query()->findOrFail($id));
    }

    public function all(): Collection
    {
        return ManufacturerData::collect(Manufacturer::query()->get(), Collection::class);
    }

    public function firstByMfaId(int $mfaId): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->where('mfa_id', $mfaId)->first());
    }

    public function minMfaId(): int
    {
        return (int) Manufacturer::query()->min('mfa_id');
    }
}
