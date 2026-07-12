<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Repositories;

use App\Vehicles\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\ModelData\ModificationData;
use App\Vehicles\Catalog\Infrastructure\Models\EngineModification;
use App\Vehicles\Catalog\Infrastructure\Models\Modification;

final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    public function firstByModIdAndType(int $modId, string $type): ?ModificationData
    {
        return ModificationData::optional(
            Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->first(),
        );
    }

    public function engineModificationCountByModIdAndType(int $modId, string $type): ?int
    {
        $modification = Modification::query()
            ->where('mod_id', $modId)
            ->where('type', $type)
            ->first();

        if ($modification === null) {
            return null;
        }

        return EngineModification::query()->where('modification_id', $modification->id)->count();
    }
}
