<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;

/**
 * Читает модификаций через Eloquent-модель фичи Catalog.
 */
final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    /**
     * Возвращает первый Data-снимок модификаций по внешнему идентификатору.
     */
    public function findByModIdAndType(int $modId, string $type): ?ModificationData
    {
        return ModificationData::optional(
            Modification::query()
                ->where('mod_id', $modId)
                ->where('type', $type)
                ->first(),
        );
    }

}
