<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Repositories;

use App\Vehicles\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Import\Domain\ModelData\EngineData;
use App\Vehicles\Import\Domain\ModelData\ModificationData;
use App\Vehicles\Import\Infrastructure\Models\Modification;
use Illuminate\Support\Collection;

final readonly class ModificationRepository implements ModificationRepositoryInterface
{
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
            'engines' => EngineData::collect($modification->engines, Collection::class),
        ]);
    }
}
