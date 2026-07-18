<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\FeatureValueRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\FeatureValueData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\FeatureValue;

final readonly class FeatureValueRepository implements FeatureValueRepositoryInterface
{
    public function firstByName(string $name): ?FeatureValueData
    {
        return FeatureValueData::optional(FeatureValue::query()->where('name', $name)->first());
    }
}
