<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\Services;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Services\WarehouseCatalogCascadeDeleteServiceInterface;

final readonly class WarehouseCatalogCascadeDeleteService implements WarehouseCatalogCascadeDeleteServiceInterface
{
    /**
     * Получает ports чтения и записи для каскадного удаления связей Warehouse-каталога.
     *
     * Шаги:
     * 1) Принять repositories, которые находят связанные номенклатуры и комплекты.
     * 2) Принять commands, которые выполняют batch delete найденных записей.
     */
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private KitRepositoryInterface $kits,
        private NomenclatureCommandInterface $nomenclatureCommand,
        private KitCommandInterface $kitCommand,
    ) {}

    /**
     * Удаляет номенклатуры, связанные с брендом.
     *
     * Шаги:
     * 1) Найти идентификаторы номенклатур по brandId.
     * 2) Передать список идентификаторов в команду записи удаления.
     * 3) Завершить без результата, если связанных записей нет.
     */
    public function deleteNomenclaturesByBrandId(int $brandId): void
    {
        $this->nomenclatureCommand->deleteByIds(
            $this->nomenclatures->findIdsByBrandId($brandId)->all(),
        );
    }

    /**
     * Удаляет комплекты, связанные с габаритом упаковки.
     *
     * Шаги:
     * 1) Найти идентификаторы комплектов по packDimensionId.
     * 2) Передать список идентификаторов в команду записи удаления.
     * 3) Завершить без результата, если связанных записей нет.
     */
    public function deleteKitsByPackDimensionId(int $packDimensionId): void
    {
        $this->kitCommand->deleteByIds(
            $this->kits->findIdsByPackDimensionId($packDimensionId)->all(),
        );
    }
}
