<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\WiperAdapterAudit\Infrastructure\Repositories;

use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Repositories\WiperAdapterAuditKitRepositoryInterface;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\ModelData\KitData;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Infrastructure\Models\Kit;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-наборы с составом для аудита адаптеров дворников.
 */
final readonly class WiperAdapterAuditKitRepository implements WiperAdapterAuditKitRepositoryInterface
{
    /**
     * Возвращает наборы, подходящие под старый критерий аудита: минимум три позиции состава.
     * Шаги:
     * 1) Построить Eloquent query Warehouse kits с eager-load nomenclatures.type.
     * 2) Ограничить наборы relation-count условием: nomenclatures >= 3.
     * 3) Отсортировать kits по id для стабильного отчёта.
     * 4) Преобразовать модели в Collection<int, KitData>.
     *
     * @return Collection<int, KitData>
     */
    public function withAtLeastThreeNomenclatures(): Collection
    {
        $kits = Kit::query()
            ->with(['nomenclatures.type'])
            ->has(
                relation: 'nomenclatures',
                operator: '>=',
                count: 3,
            )
            ->orderBy('id')
            ->get();

        return KitData::collect($kits, Collection::class);
    }
}
