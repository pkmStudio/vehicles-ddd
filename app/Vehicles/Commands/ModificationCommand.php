<?php

declare(strict_types=1);

namespace App\Vehicles\Commands;

use App\Vehicles\Commands\Contracts\ModificationCommandInterface;
use App\Vehicles\Models\Modification;

final class ModificationCommand implements ModificationCommandInterface
{
    public function create(array $attributes): Modification
    {
        return Modification::query()->create($attributes);
    }

    public function update(Modification $modification, array $attributes): Modification
    {
        $modification->update($attributes);

        return $modification;
    }

    public function updateOrCreate(array $attributes, array $values = []): Modification
    {
        return Modification::query()->updateOrCreate($attributes, $values);
    }

    public function delete(Modification $modification): bool
    {
        return (bool) $modification->delete();
    }
}
