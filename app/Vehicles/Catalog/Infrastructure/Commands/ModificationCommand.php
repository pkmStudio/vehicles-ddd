<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Commands;

use App\Vehicles\Catalog\Domain\Contracts\Commands\ModificationCommandInterface;
use App\Vehicles\Catalog\Domain\ModelData\ModificationData;
use App\Vehicles\Catalog\Infrastructure\Models\Modification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final readonly class ModificationCommand implements ModificationCommandInterface
{
    public function create(ModificationData $data): ModificationData
    {
        return DB::transaction(
            fn (): ModificationData => ModificationData::from(
                Modification::query()->create(Arr::except($data->toArray(), ['id'])),
            ),
        );
    }

    public function update(ModificationData $data): ModificationData
    {
        return DB::transaction(function () use ($data): ModificationData {
            $modification = Modification::query()
                ->where('mod_id', $data->modId)
                ->where('type', $data->type->value)
                ->firstOrFail();
            $modification->fill(Arr::except($data->toArray(), ['id']));
            $modification->save();

            return ModificationData::from($modification->refresh());
        });
    }

    public function deleteByModIdAndType(int $modId, string $type): void
    {
        DB::transaction(function () use ($modId, $type): void {
            Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->delete();
        });
    }
}
