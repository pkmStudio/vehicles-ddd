<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\Import\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Collection;

/**
 * Читает уже сохранённую Warehouse-номенклатуру по артикулам — для сборки состава Kit.
 */
final readonly class NomenclatureRepository implements NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру по id или null.
     *
     * Шаги:
     * 1) Передать поиск в общий helper по колонке id.
     * 2) Получить NomenclatureData или null.
     * 3) Вернуть результат вызывающему сервису импорта.
     */
    public function findById(int $id): ?NomenclatureData
    {
        return $this->findByColumn('id', $id);
    }

    /**
     * Возвращает номенклатуру по артикулу или null.
     *
     * Шаги:
     * 1) Передать поиск в общий helper по колонке part_number.
     * 2) Получить NomenclatureData или null.
     * 3) Вернуть результат вызывающему сервису импорта.
     */
    public function findByPartNumber(string $partNumber): ?NomenclatureData
    {
        return $this->findByColumn('part_number', $partNumber);
    }

    /**
     * Возвращает найденные номенклатуры (с загруженным типом), индексированные по part_number.
     *
     * Шаги:
     * 1) Прочитать номенклатуры по списку part_number с relation type.
     * 2) Преобразовать Eloquent collection в Collection<NomenclatureData>.
     * 3) Переиндексировать результат по partNumber.
     *
     * @param  array<int, string>  $partNumbers
     * @return Collection<string, NomenclatureData>
     */
    public function findByPartNumbers(array $partNumbers): Collection
    {
        $items = Nomenclature::query()
            ->with('type')
            ->whereIn('part_number', $partNumbers)
            ->get();

        return NomenclatureData::collect($items, Collection::class)->keyBy('partNumber');
    }

    private function findByColumn(string $column, int|string $value): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->with('type')
            ->where($column, $value)
            ->first();

        return NomenclatureData::optional($nomenclature);
    }
}
