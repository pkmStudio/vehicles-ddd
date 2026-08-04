<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureIntegrationDeletionContextDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureLookupDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Читает Warehouse-номенклатуру для Catalog-мутаций.
 */
final readonly class NomenclatureRepository implements NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по typed lookup-критерию или null.
     */
    public function find(NomenclatureLookupDTO $lookup): ?NomenclatureData
    {
        $query = Nomenclature::query();

        if ($lookup->id !== null) {
            $nomenclature = $query
                ->with(['type', 'brand'])
                ->find($lookup->id);

            return NomenclatureData::optional($nomenclature);
        }

        $nomenclature = $query
            ->where('part_number', $lookup->partNumber)
            ->first();

        return NomenclatureData::optional($nomenclature);
    }

    /**
     * Возвращает найденные номенклатуры по id с загруженным типом, индексированные по id.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, NomenclatureData>
     */
    public function findByIds(array $ids): Collection
    {
        $items = Nomenclature::query()
            ->with('type')
            ->whereIn('id', $ids)
            ->get();

        return NomenclatureData::collect($items, Collection::class)->keyBy('id');
    }

    /**
     * Возвращает ids номенклатур бренда.
     *
     * @return Collection<int, int>
     */
    public function findIdsByBrandId(int $brandId): Collection
    {
        return Nomenclature::query()
            ->where('brand_id', $brandId)
            ->pluck('id')
            ->map($this->toInteger(...))
            ->values();
    }

    /**
     * Возвращает integration contexts перед физическим удалением номенклатуры.
     *
     * @return Collection<int, NomenclatureIntegrationDeletionContextDTO>
     */
    public function deletionIntegrationContexts(int $id): Collection
    {
        $toDeletionContext = fn (object $row): NomenclatureIntegrationDeletionContextDTO => new NomenclatureIntegrationDeletionContextDTO(
            id: (int) $row->id,
            provider: (string) $row->provider,
            externalId: is_string($row->external_id) ? $row->external_id : null,
            externalCode: is_string($row->external_code) ? $row->external_code : null,
        );

        return DB::table('nomenclature_integrations')
            ->where('nomenclature_id', $id)
            ->get(['id', 'provider', 'external_id', 'external_code'])
            ->map($toDeletionContext);
    }

    private function toInteger(mixed $id): int
    {
        return (int) $id;
    }
}
