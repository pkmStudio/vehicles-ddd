<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases;

use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableCategoryDTO;
use Illuminate\Support\Collection;

/**
 * Описывает получение категорий с применимыми товарами для модификации.
 */
interface ListApplicableCategoriesUseCaseInterface
{
    /** @return Collection<int, ApplicableCategoryDTO>|null */
    public function execute(int $modificationId, int $brandId): ?Collection;
}
