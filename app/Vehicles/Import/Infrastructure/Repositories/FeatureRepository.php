<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\FeatureRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\Feature\FeatureData;
use App\Vehicles\Import\Infrastructure\Models\Feature;
use Illuminate\Support\Collection;

final readonly class FeatureRepository implements FeatureRepositoryInterface
{
    public function find(int $id): ?FeatureData
    {
        return FeatureData::optional(Feature::query()->find($id));
    }

    public function findOrFail(int $id): FeatureData
    {
        return FeatureData::from(Feature::query()->findOrFail($id));
    }

    public function all(): Collection
    {
        return FeatureData::collect(Feature::query()->get(), Collection::class);
    }
}
