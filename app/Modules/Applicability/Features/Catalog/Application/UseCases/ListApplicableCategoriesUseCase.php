<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Application\UseCases;

use App\Modules\Applicability\Features\Catalog\Domain\Contracts\Repositories\CatalogApplicabilityRepositoryInterface;
use App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases\ListApplicableCategoriesUseCaseInterface;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableCategoryDTO;
use Illuminate\Support\Collection;

/**
 * Возвращает непустые категории применимых товаров выбранного бренда.
 */
final readonly class ListApplicableCategoriesUseCase implements ListApplicableCategoriesUseCaseInterface
{
    public function __construct(
        private CatalogApplicabilityRepositoryInterface $repository,
    ) {}

    /** @return Collection<int, ApplicableCategoryDTO>|null */
    public function execute(int $modificationId, int $brandId): ?Collection
    {
        if (! $this->repository->modificationExists($modificationId)) {
            return null;
        }

        return $this->repository->categories($modificationId, $brandId);
    }
}
