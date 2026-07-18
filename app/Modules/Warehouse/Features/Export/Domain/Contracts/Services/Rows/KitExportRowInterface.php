<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\Rows;

use App\Modules\Warehouse\Features\Export\Domain\ModelData\KitData;

/**
 * Порт построения строки Excel для Warehouse-набора.
 */
interface KitExportRowInterface
{
    /**
     * Возвращает заголовки листа наборов.
     *
     * @return array<int, string>
     */
    public function getHeadings(): array;

    /**
     * Возвращает значения одной строки набора.
     *
     * @return array<int, mixed>
     */
    public function getData(KitData $kit): array;
}
