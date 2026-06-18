<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Application\Contracts\Commands\FeatureCommandInterface;
use App\Vehicles\Domain\Models\Feature;

final class FeatureCommand implements FeatureCommandInterface
{
    public function create(array $attributes): Feature
    {
        return Feature::query()->create($attributes);
    }

    public function update(Feature $feature, array $attributes): Feature
    {
        $feature->update($attributes);

        return $feature;
    }

    public function updateOrCreate(array $attributes, array $values = []): Feature
    {
        return Feature::query()->updateOrCreate($attributes, $values);
    }

    public function delete(Feature $feature): bool
    {
        return (bool) $feature->delete();
    }
}
