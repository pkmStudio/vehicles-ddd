<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\Manufacturer;

use App\Vehicles\Models\Manufacturer;
use App\Vehicles\Repositories\Manufacturer\ManufacturerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    public function find(int $id): ?Manufacturer
    {
        return Manufacturer::query()->find($id);
    }

    public function findOrFail(int $id): Manufacturer
    {
        return Manufacturer::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return Manufacturer::query()->get();
    }

    public function firstByMfaId(int $mfaId): ?Manufacturer
    {
        return Manufacturer::query()->where('mfa_id', $mfaId)->first();
    }
}
