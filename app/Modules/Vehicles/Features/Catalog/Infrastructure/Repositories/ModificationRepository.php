<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ModificationRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ModificationData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\EngineModification;
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

    /**
     * Возвращает ids модификаций по vehicle ids.
     *
     * @param  array<int, int>  $vehicleIds
     * @return Collection<int, int>
     */
    public function findIdsByVehicleIds(array $vehicleIds): Collection
    {
        if ($vehicleIds === []) {
            return collect();
        }

        return Modification::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    /**
     * Возвращает ids связок модификаций с двигателями.
     *
     * @param  array<int, int>  $modificationIds
     * @return Collection<int, int>
     */
    public function findEngineModificationIdsByModificationIds(array $modificationIds): Collection
    {
        if ($modificationIds === []) {
            return collect();
        }

        return EngineModification::query()
            ->whereIn('modification_id', $modificationIds)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    private function toInteger(mixed $id): int
    {
        return (int) $id;
    }
}
