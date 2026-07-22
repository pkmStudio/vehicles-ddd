<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureDeletionBlockersDTO;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\NomenclatureIntegrationDeletionContextDTO;
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
     * Возвращает номенклатуру по id или null.
     */
    public function findById(int $id): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->with(['type', 'brand'])
            ->find($id);

        return NomenclatureData::optional($nomenclature);
    }

    /**
     * Возвращает первую номенклатуру по артикулу или null.
     */
    public function findByPartNumber(string $partNumber): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->where('part_number', $partNumber)
            ->first();

        return NomenclatureData::optional($nomenclature);
    }

    /**
     * Проверяет, занят ли артикул другой номенклатурой.
     */
    public function partNumberExistsForAnother(string $partNumber, int $id): bool
    {
        return Nomenclature::query()
            ->where('part_number', $partNumber)
            ->where('id', '!=', $id)
            ->exists();
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
     * Собирает зависимости, блокирующие удаление номенклатуры.
     */
    public function deletionBlockers(int $id): ?NomenclatureDeletionBlockersDTO
    {
        if (! Nomenclature::query()->whereKey($id)->exists()) {
            return null;
        }

        return new NomenclatureDeletionBlockersDTO(
            kitsCount: DB::table('kit_nomenclature')->where('nomenclature_id', $id)->count(),
            integrationsCount: DB::table('nomenclature_integrations')
                ->where('nomenclature_id', $id)
                ->where('provider', '!=', 'moysklad')
                ->count(),
        );
    }

    /**
     * Возвращает integration contexts перед физическим удалением номенклатуры.
     *
     * @return Collection<int, NomenclatureIntegrationDeletionContextDTO>
     */
    public function deletionIntegrationContexts(int $id): Collection
    {
        return DB::table('nomenclature_integrations')
            ->where('nomenclature_id', $id)
            ->get(['id', 'provider', 'external_id', 'external_code'])
            ->map(fn (object $row): NomenclatureIntegrationDeletionContextDTO => new NomenclatureIntegrationDeletionContextDTO(
                id: (int) $row->id,
                provider: (string) $row->provider,
                externalId: is_string($row->external_id) ? $row->external_id : null,
                externalCode: is_string($row->external_code) ? $row->external_code : null,
            ));
    }
}
