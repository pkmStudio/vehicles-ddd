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
    public function __construct(
        private NomenclatureRepositoryInterface $nomenclatures,
        private KitRepositoryInterface $kits,
        private NomenclatureCommandInterface $nomenclatureCommand,
        private KitCommandInterface $kitCommand,
    ) {}

    public function deleteNomenclaturesByBrandId(int $brandId): void
    {
        $this->nomenclatureCommand->deleteByIds(
            $this->nomenclatures->findIdsByBrandId($brandId)->all(),
        );
    }

    public function deleteKitsByPackDimensionId(int $packDimensionId): void
    {
        $this->kitCommand->deleteByIds(
            $this->kits->findIdsByPackDimensionId($packDimensionId)->all(),
        );
    }
}
