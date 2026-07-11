<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\FeatureValueData;
use App\Vehicles\Import\Infrastructure\Models\FeatureValue;
use Illuminate\Support\Collection;

final readonly class FeatureValueRepository implements FeatureValueRepositoryInterface
{
    public function find(int $id): ?FeatureValueData
    {
        return FeatureValueData::optional(FeatureValue::query()->find($id));
    }

    public function findOrFail(int $id): FeatureValueData
    {
        return FeatureValueData::from(FeatureValue::query()->findOrFail($id));
    }

    public function all(): Collection
    {
        return FeatureValueData::collect(FeatureValue::query()->get(), Collection::class);
    }

    public function firstByName(string $name): ?FeatureValueData
    {
        return FeatureValueData::optional(FeatureValue::query()->where('name', $name)->first());
    }
}
