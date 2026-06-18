<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Commands\Modification;

use App\Vehicles\Infrastructure\Commands\Modification\ModificationCommandInterface;
use App\Vehicles\Application\DTOs\Modification\ModificationData;
use App\Vehicles\Domain\Models\Modification;

final class ModificationCommand implements ModificationCommandInterface
{
    public function create(ModificationData $data): Modification
    {
        return Modification::query()->create($data->toArray());
    }

    public function update(Modification $modification, ModificationData $data): Modification
    {
        $modification->update($data->toArray());

        return $modification;
    }

    public function upsertByModIdAndType(ModificationData $data): Modification
    {
        return Modification::query()->updateOrCreate(
            ['mod_id' => $data->modId, 'type' => $data->type],
            $data->toArray(),
        );
    }

    public function delete(Modification $modification): bool
    {
        return (bool) $modification->delete();
    }
}
