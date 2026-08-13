<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\Contracts\UseCases;

use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableNomenclaturePageDTO;

/**
 * Описывает страницу применимых товаров категории для выбранной модификации.
 */
interface ListApplicableNomenclaturesUseCaseInterface
{
    public function execute(
        int $modificationId,
        int $categoryId,
        int $brandId,
        int $page,
        int $pageSize,
    ): ?ApplicableNomenclaturePageDTO;
}
