<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\Feature;

use App\Vehicles\Models\Feature;
use App\Vehicles\Repositories\Feature\FeatureRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class FeatureRepository implements FeatureRepositoryInterface
{
    public function find(int $id): ?Feature
    {
        return Feature::query()->find($id);
    }

    public function findOrFail(int $id): Feature
    {
        return Feature::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return Feature::query()->get();
    }

    public function firstWhere(string $column, mixed $value): ?Feature
    {
        return Feature::query()->where($column, $value)->first();
    }
}
