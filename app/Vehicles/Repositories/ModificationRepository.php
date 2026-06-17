<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories;

use App\Vehicles\Models\Modification;
use App\Vehicles\Repositories\Contracts\ModificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class ModificationRepository implements ModificationRepositoryInterface
{
    public function find(int $id): ?Modification
    {
        return Modification::query()->find($id);
    }

    public function findOrFail(int $id): Modification
    {
        return Modification::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return Modification::query()->get();
    }

    public function firstWhere(string $column, mixed $value): ?Modification
    {
        return Modification::query()->where($column, $value)->first();
    }
}
