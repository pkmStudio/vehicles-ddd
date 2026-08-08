<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\UseCases\Nomenclature\Catalog\ShowCatalogNomenclatureUseCaseInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;

final readonly class ShowCatalogNomenclatureUseCase implements ShowCatalogNomenclatureUseCaseInterface
{
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    public function execute(string $partNumber, int $brandId): ?CatalogNomenclatureDTO
    {
        return $this->repository->findByPartNumber($partNumber, $brandId);
    }
}
