<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\FeatureValueCommandInterface;
use App\Vehicles\Import\Domain\ModelData\FeatureValue\FeatureValueData;
use App\Vehicles\Import\Infrastructure\Models\FeatureValue;
use Illuminate\Support\Arr;

final readonly class FeatureValueCommand implements FeatureValueCommandInterface
{
    public function create(FeatureValueData $data): FeatureValueData
    {
        return FeatureValueData::from(
            FeatureValue::query()->create(Arr::except($data->toArray(), ['id'])),
        );
    }

    public function update(FeatureValueData $data): FeatureValueData
    {
        $featureValue = FeatureValue::query()->findOrFail($data->id);
        $featureValue->update(Arr::except($data->toArray(), ['id']));

        return FeatureValueData::from($featureValue);
    }

    public function upsertByName(FeatureValueData $data): FeatureValueData
    {
        return FeatureValueData::from(
            FeatureValue::query()->updateOrCreate(['name' => $data->name], Arr::except($data->toArray(), ['id'])),
        );
    }

    public function delete(FeatureValueData $data): bool
    {
        return (bool) FeatureValue::query()->whereKey($data->id)->delete();
    }
}
