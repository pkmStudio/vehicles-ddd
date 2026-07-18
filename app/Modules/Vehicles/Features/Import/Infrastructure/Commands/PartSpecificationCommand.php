<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\PartSpecificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\PartSpecificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\PartSpecification;
use Illuminate\Support\Arr;

final readonly class PartSpecificationCommand implements PartSpecificationCommandInterface
{
    public function create(PartSpecificationData $data): PartSpecificationData
    {
        return PartSpecificationData::from(
            PartSpecification::query()->create(Arr::except($data->toArray(), ['id'])),
        );
    }

    public function update(PartSpecificationData $data): PartSpecificationData
    {
        $specification = PartSpecification::query()->findOrFail($data->id);
        $specification->update(Arr::except($data->toArray(), ['id']));

        return PartSpecificationData::from($specification);
    }

    public function upsert(PartSpecificationData $data): PartSpecificationData
    {
        return PartSpecificationData::from(
            PartSpecification::query()->updateOrCreate(
                [
                    'partable_type' => $data->partableType,
                    'partable_id' => $data->partableId,
                    'template' => $data->template,
                    'feature_value_id' => $data->featureValueId,
                ],
                [
                    'name' => $data->name,
                    'text' => $data->text,
                    'details' => $data->details,
                ],
            ),
        );
    }
}
