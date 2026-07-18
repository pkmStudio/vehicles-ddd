<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Repositories;

use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\ModelData\KitData;
use Illuminate\Support\Collection;

/**
 * Порт чтения Warehouse-наборов для аудита адаптеров дворников.
 */
interface WiperAdapterAuditKitRepositoryInterface
{
    /**
     * Возвращает наборы с составом, где есть минимум три позиции номенклатуры.
     *
     * @return Collection<int, KitData>
     */
    public function withAtLeastThreeNomenclatures(): Collection;
}
