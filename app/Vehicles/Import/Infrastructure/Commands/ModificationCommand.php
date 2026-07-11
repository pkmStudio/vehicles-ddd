<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Commands;

use App\Vehicles\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Import\Domain\ModelData\ModificationData;
use App\Vehicles\Import\Infrastructure\Models\Modification;
use Illuminate\Support\Arr;

final readonly class ModificationCommand implements ModificationCommandInterface
{
    /** Служебные поля ModificationData, отсутствующие как колонки в modifications. */
    private const array NON_COLUMN_FIELDS = ['id', 'engines'];

    public function create(ModificationData $data): ModificationData
    {
        return ModificationData::from(
            Modification::query()->create(Arr::except($data->toArray(), self::NON_COLUMN_FIELDS)),
        );
    }

    public function update(ModificationData $data): ModificationData
    {
        $modification = Modification::query()->findOrFail($data->id);
        $modification->update(Arr::except($data->toArray(), self::NON_COLUMN_FIELDS));

        return ModificationData::from($modification);
    }

    public function upsertByModIdAndType(ModificationData $data): ModificationData
    {
        return ModificationData::from(
            Modification::query()->updateOrCreate(
                ['mod_id' => $data->modId, 'type' => $data->type],
                Arr::except($data->toArray(), self::NON_COLUMN_FIELDS),
            ),
        );
    }

    public function delete(ModificationData $data): bool
    {
        return (bool) Modification::query()->whereKey($data->id)->delete();
    }
}
