<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Models\Manufacturer;
use App\Vehicles\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
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
