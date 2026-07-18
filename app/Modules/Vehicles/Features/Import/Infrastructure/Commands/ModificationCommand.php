<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Commands;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Modification;
use Illuminate\Support\Arr;

final readonly class ModificationCommand implements ModificationCommandInterface
{
    /** Служебные поля ModificationData, отсутствующие как колонки в modifications. */
    private const array NON_COLUMN_FIELDS = ['id', 'engines'];

    public function upsertByModIdAndType(ModificationData $data): ModificationData
    {
        return ModificationData::from(
            Modification::query()->updateOrCreate(
                ['mod_id' => $data->modId, 'type' => $data->type],
                Arr::except($data->toArray(), self::NON_COLUMN_FIELDS),
            ),
        );
    }
}
