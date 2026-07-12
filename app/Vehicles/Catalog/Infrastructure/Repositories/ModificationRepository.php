<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Infrastructure\Repositories;

use App\Vehicles\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Vehicles\Catalog\Domain\ModelData\ModificationData;
use App\Vehicles\Catalog\Infrastructure\Models\EngineModification;
use App\Vehicles\Catalog\Infrastructure\Models\Modification;

/**
 * Читает модификаций через Eloquent-модель фичи Catalog.
 */
final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок модификаций по внешнему идентификатору.
     */
    public function firstByModIdAndType(int $modId, string $type): ?ModificationData
    {
        return ModificationData::optional(
            Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->first(),
        );
    }

    /**
     * Возвращает количество связанных записей, блокирующих удаление.
     */
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
