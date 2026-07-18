<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\Rows;

use App\Modules\Warehouse\Features\Export\Domain\ModelData\NomenclatureData;

/**
 * Порт построения базовой строки Excel для Warehouse-номенклатуры.
 */
interface NomenclatureExportRowInterface
{
    /**
     * Возвращает базовые заголовки, общие для всех типов номенклатуры.
     *
     * @return array<int, string>
     */
    public function getBaseHeadings(): array;

    /**
     * Возвращает базовые значения номенклатуры без detail-полей.
     *
     * @return array<int, mixed>
     */
    public function getBaseData(NomenclatureData $nomenclature): array;
}
