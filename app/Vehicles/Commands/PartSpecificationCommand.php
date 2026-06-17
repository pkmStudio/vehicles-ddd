<?php

declare(strict_types=1);

namespace App\Vehicles\Commands;

use App\Vehicles\Commands\Contracts\PartSpecificationCommandInterface;
use App\Vehicles\Models\PartSpecification;

final class PartSpecificationCommand implements PartSpecificationCommandInterface
{
    public function create(array $attributes): PartSpecification
    {
        return PartSpecification::query()->create($attributes);
    }

    public function update(PartSpecification $partSpecification, array $attributes): PartSpecification
    {
        $partSpecification->update($attributes);

        return $partSpecification;
    }

    public function updateOrCreate(array $attributes, array $values = []): PartSpecification
    {
        return PartSpecification::query()->updateOrCreate($attributes, $values);
    }

    public function delete(PartSpecification $partSpecification): bool
    {
        return (bool) $partSpecification->delete();
    }
}
