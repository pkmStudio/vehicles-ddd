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
     * Шаги:
     * 1) Найти Warehouse kits, у которых в составе минимум три номенклатуры.
     * 2) Загрузить состав и типы номенклатур для расчёта adapters mismatch.
     * 3) Вернуть коллекцию KitData для application service аудита.
     *
     * @return Collection<int, KitData>
     */
    public function withAtLeastThreeNomenclatures(): Collection;
}
