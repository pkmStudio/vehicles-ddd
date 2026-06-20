<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Domain\Contracts\Commands\FeatureValueCommandInterface;
use App\Vehicles\Domain\ModelData\FeatureValue\FeatureValueData;
use App\Vehicles\Domain\Models\FeatureValue;

final readonly class FeatureValueCommand implements FeatureValueCommandInterface
{
    public function create(FeatureValueData $data): FeatureValue
    {
        return FeatureValue::query()->create($data->toArray());
    }

    public function update(FeatureValue $featureValue, FeatureValueData $data): FeatureValue
    {
        $featureValue->update($data->toArray());

        return $featureValue;
    }

    public function upsertByName(FeatureValueData $data): FeatureValue
    {
        return FeatureValue::query()->updateOrCreate(['name' => $data->name], $data->toArray());
    }

    public function delete(FeatureValue $featureValue): bool
    {
        return (bool) $featureValue->delete();
    }
}
