<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Application\UseCases\Nomenclature\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Repositories\NomenclatureCatalogRepositoryInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Catalog\CatalogNomenclatureDTO;

/**
 * Возвращает детальную номенклатуру публичного каталога по артикулу.
 */
final readonly class ShowCatalogNomenclatureUseCase
{
    /**
     * Получает read repository публичного каталога.
     */
    public function __construct(
        private NomenclatureCatalogRepositoryInterface $repository,
    ) {}

    /**
     * Делегирует регистронезависимый поиск артикула repository.
     */
    public function execute(string $partNumber, int $brandId): ?CatalogNomenclatureDTO
    {
        return $this->repository->findByPartNumber(
            partNumber: $partNumber,
            brandId: $brandId,
        );
    }
}
