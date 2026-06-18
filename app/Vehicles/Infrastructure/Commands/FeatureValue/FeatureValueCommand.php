<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands\FeatureValue;

use App\Vehicles\Infrastructure\Commands\FeatureValue\FeatureValueCommandInterface;
use App\Vehicles\Domain\Models\FeatureValue;

final class FeatureValueCommand implements FeatureValueCommandInterface
{
    public function create(array $attributes): FeatureValue
    {
        return FeatureValue::query()->create($attributes);
    }

    public function update(FeatureValue $featureValue, array $attributes): FeatureValue
    {
        $featureValue->update($attributes);

        return $featureValue;
    }

    public function updateOrCreate(array $attributes, array $values = []): FeatureValue
    {
        return FeatureValue::query()->updateOrCreate($attributes, $values);
    }

    public function delete(FeatureValue $featureValue): bool
    {
        return (bool) $featureValue->delete();
    }
}
