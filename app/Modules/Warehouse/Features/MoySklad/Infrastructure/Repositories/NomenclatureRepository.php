<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\ModelData\NomenclatureData;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Models\Nomenclature;
use Generator;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация чтения Warehouse-номенклатуры для MoySklad-фичи.
 */
final readonly class NomenclatureRepository implements NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру с типом и брендом по id или null.
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
     *
     * @return Generator<int, NomenclatureData>
     */
    public function cursorById(int $chunk): Generator
    {
        $lastId = 0;
        $chunk = max(1, $chunk);

        while (true) {
            $nomenclatures = Nomenclature::query()
                ->with(['type', 'brand'])
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            if ($nomenclatures->isEmpty()) {
                return;
            }

            foreach (NomenclatureData::collect($nomenclatures, Collection::class) as $nomenclature) {
                yield $nomenclature;
            }

            $lastId = (int) $nomenclatures->last()->id;
        }
    }
}
