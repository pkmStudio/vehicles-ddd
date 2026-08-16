<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Export\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Export\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Export\Infrastructure\Models\Modification;
use Illuminate\Support\Collection;

/**
 * Читает modification snapshots для Excel export.
 */
final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    /**
     * Возвращает все модификации для manager export.
     *
     * Шаги:
     * 1) Прочитать Eloquent-модели модификаций в стабильном порядке.
     * 2) Сконвертировать collection в typed `ModificationData`.
     */
    public function all(): Collection
    {
        return ModificationData::collect(
            Modification::query()
                ->orderBy('ms_id')
                ->orderBy('mod_id')
                ->orderBy('type')
                ->get(),
            Collection::class,
        );
    }
}
