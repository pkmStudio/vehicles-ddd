<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\FeatureValueData;
use App\Vehicles\Import\Infrastructure\Models\FeatureValue;

final readonly class FeatureValueRepository implements FeatureValueRepositoryInterface
{
    public function firstByName(string $name): ?FeatureValueData
    {
        return FeatureValueData::optional(FeatureValue::query()->where('name', $name)->first());
    }
}
