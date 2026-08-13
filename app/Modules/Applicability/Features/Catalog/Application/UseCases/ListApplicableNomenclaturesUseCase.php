<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Application\UseCases;

use App\Modules\Applicability\Features\Catalog\Domain\Contracts\Repositories\CatalogApplicabilityRepositoryInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\ListApplicableNomenclaturesUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableNomenclaturePageDTO;

/**
 * Возвращает страницу применимых товаров категории выбранного бренда.
 */
final readonly class ListApplicableNomenclaturesUseCase implements ListApplicableNomenclaturesUseCaseInterface
{
    public function __construct(
        private CatalogApplicabilityRepositoryInterface $repository,
    ) {}

    public function execute(
        int $modificationId,
        int $categoryId,
        int $brandId,
        int $page,
        int $pageSize,
    ): ?ApplicableNomenclaturePageDTO {
        if (! $this->repository->modificationExists($modificationId)) {
            return null;
        }

        return $this->repository->paginateNomenclatures(
            modificationId: $modificationId,
            categoryId: $categoryId,
            brandId: $brandId,
            page: $page,
            pageSize: $pageSize,
        );
    }
}
