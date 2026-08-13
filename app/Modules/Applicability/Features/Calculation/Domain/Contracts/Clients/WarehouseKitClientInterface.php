<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients;

use App\Modules\Applicability\Features\Calculation\Domain\ModelData\KitData;

interface WarehouseKitClientInterface
{
    /**
     * Возвращает активные Warehouse kits для расчета применяемости.
     *
     * Шаги:
     * 1. Применяет optional фильтр по конкретному kit id.
     * 2. Читает активные комплекты чанками, чтобы не грузить весь каталог в память.
     * 3. Отдает локальные `KitData` снимки расчетному use case.
     *
     * @return iterable<int, KitData>
     */
    public function activeKits(?int $kitId = null, int $chunk = 1000): iterable;
}
