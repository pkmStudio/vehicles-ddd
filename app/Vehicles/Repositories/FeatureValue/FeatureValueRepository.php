<?php

declare(strict_types=1);

namespace App\Vehicles\Repositories\FeatureValue;

use App\Vehicles\Models\FeatureValue;
use App\Vehicles\Repositories\FeatureValue\FeatureValueRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class FeatureValueRepository implements FeatureValueRepositoryInterface
{
    public function find(int $id): ?FeatureValue
    {
        return FeatureValue::query()->find($id);
    }

    public function findOrFail(int $id): FeatureValue
    {
        return FeatureValue::query()->findOrFail($id);
    }

    public function all(): Collection
    {
        return FeatureValue::query()->get();
    }

    public function firstByName(string $name): ?FeatureValue
    {
        return FeatureValue::query()->where('name', $name)->first();
    }
}
