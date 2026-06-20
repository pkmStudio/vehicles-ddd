<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Domain\Contracts\Commands\FeatureCommandInterface;
use App\Vehicles\Domain\ModelData\Feature\FeatureData;
use App\Vehicles\Domain\Models\Feature;

final readonly class FeatureCommand implements FeatureCommandInterface
{
    public function create(FeatureData $data): Feature
    {
        return Feature::query()->create($data->toArray());
    }

    public function update(Feature $feature, FeatureData $data): Feature
    {
        $feature->update($data->toArray());

        return $feature;
    }

    public function upsertByName(FeatureData $data): Feature
    {
        return Feature::query()->updateOrCreate(['name' => $data->name], $data->toArray());
    }

    public function delete(Feature $feature): bool
    {
        return (bool) $feature->delete();
    }
}
