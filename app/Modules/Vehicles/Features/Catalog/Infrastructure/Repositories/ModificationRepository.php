<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Modification;
use Illuminate\Support\Collection;

/**
 * Читает модификаций через Eloquent-модель фичи Catalog.
 */
final readonly class ModificationRepository implements ModificationRepositoryInterface
{
    /**
     * Возвращает модификацию по внутреннему идентификатору.
     */
    public function findById(int $id): ?ModificationData
    {
        return ModificationData::optional(Modification::query()->find($id));
    }

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

    /**
     * Возвращает модификации ТС.
     *
     * @return Collection<int, ModificationData>
     */
    public function findByVehicleId(int $vehicleId): Collection
    {
        $modifications = Modification::query()
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('year_from')
            ->orderBy('id')
            ->get();

        return ModificationData::collect($modifications, Collection::class);
    }
}
