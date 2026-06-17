<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories;

use App\Vehicles\Models\PartSpecification;
use App\Vehicles\Repositories\Contracts\PartSpecificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class PartSpecificationRepository implements PartSpecificationRepositoryInterface
{
    public function find(int $id): ?PartSpecification
    {
        return PartSpecification::query()->find($id);
    }

    public function findOrFail(int $id): PartSpecification
    {
        return PartSpecification::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return PartSpecification::query()->get();
    }

    public function firstWhere(string $column, mixed $value): ?PartSpecification
    {
        return PartSpecification::query()->where($column, $value)->first();
    }
}
