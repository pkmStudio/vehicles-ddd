<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Repositories\ManufacturerRepositoryInterface;
use App\Modules\Vehicles\Features\Catalog\Domain\ModelData\ManufacturerData;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Manufacturer;
use App\Modules\Vehicles\Features\Catalog\Infrastructure\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * Читает производителей через Eloquent-модель фичи Catalog.
 */
final readonly class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    /**
     * Возвращает производителя по внутреннему идентификатору.
     */
    public function findById(int $id): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->find($id));
    }

    /**
     * Возвращает первый Data-снимок производителей по внешнему идентификатору.
     */
    public function findByMfaId(int $mfaId): ?ManufacturerData
    {
        return ManufacturerData::optional(Manufacturer::query()->where('mfa_id', $mfaId)->first());
    }

    /**
     * Возвращает производителей, у которых есть разрешённые ТС.
     *
     * @return Collection<int, ManufacturerData>
     */
    public function findAllWithAllowedVehicles(): Collection
    {
        $manufacturers = Manufacturer::query()
            ->whereIn(
                'id',
                Vehicle::query()
                    ->select('manufacturer_id')
                    ->where('is_allow', true),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return ManufacturerData::collect($manufacturers, Collection::class);
    }
}
