<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Infrastructure\Repositories;

use App\Warehouse\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\MoySklad\Domain\ModelData\NomenclatureData;
use App\Warehouse\MoySklad\Infrastructure\Models\Nomenclature;
use Illuminate\Support\Collection;

/**
 * Eloquent-реализация чтения Warehouse-номенклатуры для MoySklad-фичи.
 */
final readonly class NomenclatureRepository implements NomenclatureRepositoryInterface
{
    /**
     * Возвращает номенклатуру с типом и брендом по id или null.
     */
    public function find(int $id): ?NomenclatureData
    {
        $nomenclature = Nomenclature::query()
            ->with(['type', 'brand'])
            ->find($id);

        return NomenclatureData::optional($nomenclature);
    }

    /**
     * Итерирует номенклатуру чанками и отдаёт application-слою Data-снимки.
     */
    public function chunkById(int $chunk, callable $callback): void
    {
        Nomenclature::query()
            ->with(['type', 'brand'])
            ->orderBy('id')
            ->chunkById(max(1, $chunk), function (Collection $nomenclatures) use ($callback): void {
                $callback(NomenclatureData::collect($nomenclatures, Collection::class));
            });
    }
}
