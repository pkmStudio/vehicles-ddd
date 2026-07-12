<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Infrastructure\Repositories;

use App\Warehouse\WiperAdapterAudit\Domain\Contracts\Repositories\WiperAdapterAuditKitRepositoryInterface;
use App\Warehouse\WiperAdapterAudit\Domain\ModelData\KitData;
use App\Warehouse\WiperAdapterAudit\Infrastructure\Models\Kit;
use Illuminate\Support\Collection;

/**
 * Читает Warehouse-наборы с составом для аудита адаптеров дворников.
 */
final readonly class WiperAdapterAuditKitRepository implements WiperAdapterAuditKitRepositoryInterface
{
    /**
     * Возвращает наборы, подходящие под старый критерий аудита: минимум три позиции состава.
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
