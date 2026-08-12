<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Nomenclature;
use Generator;

/**
 * Eloquent-реализация чтения Warehouse-номенклатуры для MoySklad-фичи.
 */
final readonly class NomenclatureRepository implements NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру с типом и брендом по id или null.
     * Шаги:
     * 1) Построить Eloquent query номенклатуры.
     * 2) Eager-load type и brand для payload МойСклад.
     * 3) Найти модель по id и вернуть optional Data-снимок.
     */
    public function findById(int $id): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->with(['type', 'brand'])
            ->find($id);

        return NomenclatureData::optional($nomenclature);
    }

    /**
     * Возвращает номенклатуру как поток Data-снимков, читая БД чанками по id.
     * Шаги:
     * 1) Построить Eloquent query с eager-load type и brand.
     * 2) Читать модели через lazyById с chunk не меньше 1.
     * 3) Для каждой модели yield-ить NomenclatureData.
     *
     * @return Generator<int, NomenclatureData>
     */
    public function cursorById(int $chunk): Generator
    {
        $nomenclatures = Nomenclature::query()
            ->with(['type', 'brand'])
            ->lazyById(max(1, $chunk));

        foreach ($nomenclatures as $nomenclature) {
            yield NomenclatureData::from($nomenclature);
        }
    }
}
