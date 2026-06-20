<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Repositories;

use App\Vehicles\Domain\Contracts\Repositories\PartSpecificationRepositoryInterface;
use App\Vehicles\Domain\Models\PartSpecification;
use Illuminate\Database\Eloquent\Collection;

final readonly class PartSpecificationRepository implements PartSpecificationRepositoryInterface
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
