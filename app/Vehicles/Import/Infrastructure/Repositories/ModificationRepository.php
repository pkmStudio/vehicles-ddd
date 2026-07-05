<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\Engine\EngineData;
use App\Vehicles\Import\Domain\ModelData\Modification\ModificationData;
use App\Vehicles\Import\Infrastructure\Models\Modification;
use Illuminate\Support\Collection;

final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    public function find(int $id): ?ModificationData
    {
        return ModificationData::optional(Modification::query()->find($id));
    }

    public function findOrFail(int $id): ModificationData
    {
        return ModificationData::from(Modification::query()->findOrFail($id));
    }

    public function all(): Collection
    {
        return ModificationData::collect(Modification::query()->get(), Collection::class);
    }

    public function firstByMsIdAndModIdWithEngines(int $msId, int $modId): ?ModificationData
    {
        $modification = Modification::query()
            ->where('ms_id', $msId)
            ->where('mod_id', $modId)
            ->has('engines')
            ->with('engines')
            ->first();

        if ($modification === null) {
            return null;
        }

        return ModificationData::from([
            ...$modification->toArray(),
            'engines' => EngineData::collect($modification->engines, Collection::class)->all(),
        ]);
    }
}
