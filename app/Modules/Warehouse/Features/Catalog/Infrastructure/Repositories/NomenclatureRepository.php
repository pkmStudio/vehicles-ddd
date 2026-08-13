<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
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
     * Возвращает номенклатуру по внутреннему идентификатору или null.
     * Шаги:
     * 1) Делегировать общий поиск в findByColumn() по колонке id.
     * 2) Предзагрузить type и brand, потому что detail-сценарии каталога их используют.
     */
    public function findById(int $id): ?NomenclatureData
    {
        return $this->findByColumn('id', $id, ['type', 'brand']);
    }

    /**
     * Возвращает номенклатуру по артикулу или null.
     * Шаги:
     * 1) Делегировать общий поиск в findByColumn() по колонке part_number.
     * 2) Вернуть feature-local NomenclatureData или null без Eloquent leakage.
     */
    public function findByPartNumber(string $partNumber): ?NomenclatureData
    {
        return $this->findByColumn('part_number', $partNumber);
    }

    /**
     * Возвращает найденные номенклатуры по id с загруженным типом, индексированные по id.
     * Шаги:
     * 1) Запросить Nomenclature models по списку id.
     * 2) Предзагрузить relation type для downstream правил комплектов.
     * 3) Преобразовать Eloquent collection в Collection<int, NomenclatureData>.
     * 4) Переиндексировать результат по id.
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
     * Шаги:
     * 1) Отфильтровать Nomenclature по brand_id.
     * 2) Получить только id через pluck, не загружая модели.
     * 3) Вернуть плотную values collection.
     *
     * @return Collection<int, int>
     */
    public function findIdsByBrandId(int $brandId): Collection
    {
        return Nomenclature::query()
            ->where('brand_id', $brandId)
            ->pluck('id')
            ->values();
    }

    /**
     * Возвращает integration contexts перед физическим удалением номенклатуры.
     * Шаги:
     * 1) Прочитать строки nomenclature_integrations по nomenclature_id.
     * 2) Выбрать только поля, нужные delete events/listeners.
     * 3) Привести nullable external identifiers к string|null.
     * 4) Собрать NomenclatureIntegrationDeletionContextDTO для каждого provider context.
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

    /**
     * Выполняет общий поиск номенклатуры по одной колонке.
     * Шаги:
     * 1) Построить Eloquent query с запрошенными relations.
     * 2) Добавить where по колонке и значению.
     * 3) Получить первую найденную модель.
     * 4) Преобразовать модель в NomenclatureData или вернуть null.
     *
     * @param  array<int, string>  $relations
     */
    private function findByColumn(string $column, int|string $value, array $relations = []): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->with($relations)
            ->where($column, $value)
            ->first();

        return NomenclatureData::optional($nomenclature);
    }
}
