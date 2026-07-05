<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\FeatureCommandInterface;
use App\Vehicles\Import\Domain\ModelData\Feature\FeatureData;
use App\Vehicles\Import\Infrastructure\Models\Feature;
use Illuminate\Support\Arr;

final readonly class FeatureCommand implements FeatureCommandInterface
{
    public function create(FeatureData $data): FeatureData
    {
        return FeatureData::from(
            Feature::query()->create(Arr::except($data->toArray(), ['id'])),
        );
    }

    public function update(FeatureData $data): FeatureData
    {
        $feature = Feature::query()->findOrFail($data->id);
        $feature->update(Arr::except($data->toArray(), ['id']));

        return FeatureData::from($feature);
    }

    public function upsertByName(FeatureData $data): FeatureData
    {
        return FeatureData::from(
            Feature::query()->updateOrCreate(['name' => $data->name], Arr::except($data->toArray(), ['id'])),
        );
    }

    public function delete(FeatureData $data): bool
    {
        return (bool) Feature::query()->whereKey($data->id)->delete();
    }
}
