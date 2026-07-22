<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\EngineData;
use App\Modules\Vehicles\Features\Import\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Import\Infrastructure\Models\Modification;
use Illuminate\Support\Collection;

final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    public function firstByModIdAndType(int $modId, string $type): ?ModificationData
    {
        $modification = Modification::query()
            ->where('mod_id', $modId)
            ->where('type', $type)
            ->first();

        return $modification === null ? null : ModificationData::from($modification);
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
            'engines' => EngineData::collect($modification->engines, Collection::class),
        ]);
    }
}
