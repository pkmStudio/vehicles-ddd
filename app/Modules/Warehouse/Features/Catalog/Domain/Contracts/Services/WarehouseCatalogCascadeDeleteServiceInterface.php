<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services;

interface WarehouseCatalogCascadeDeleteServiceInterface
{
    /**
     * Удаляет номенклатуры, связанные с брендом.
     *
     * Шаги:
     * 1) Найти идентификаторы номенклатур по brandId.
     * 2) Передать список идентификаторов в команду записи удаления.
     * 3) Завершить без результата, если связанных записей нет.
     */
    public function deleteNomenclaturesByBrandId(int $brandId): void;

    /**
     * Удаляет комплекты, связанные с габаритом упаковки.
     *
     * Шаги:
     * 1) Найти идентификаторы комплектов по packDimensionId.
     * 2) Передать список идентификаторов в команду записи удаления.
     * 3) Завершить без результата, если связанных записей нет.
     */
    public function deleteKitsByPackDimensionId(int $packDimensionId): void;
}
