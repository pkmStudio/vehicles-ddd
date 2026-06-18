<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Models\Modification;
use App\Vehicles\Application\Contracts\Repositories\ModificationRepositoryInterface;
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
