<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands;

use App\Vehicles\Domain\Contracts\Infrastructure\Commands\PartSpecificationCommandInterface;
use App\Vehicles\Domain\ModelData\PartSpecification\PartSpecificationData;
use App\Vehicles\Domain\Models\PartSpecification;

final readonly class PartSpecificationCommand implements PartSpecificationCommandInterface
{
    public function create(PartSpecificationData $data): PartSpecification
    {
        return PartSpecification::query()->create($data->toArray());
    }

    public function update(PartSpecification $specification, PartSpecificationData $data): PartSpecification
    {
        $specification->update($data->toArray());

        return $specification;
    }

    public function upsert(PartSpecificationData $data): PartSpecification
    {
        return PartSpecification::query()->updateOrCreate(
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
        );
    }

    public function delete(PartSpecification $specification): bool
    {
        return (bool) $specification->delete();
    }
}
