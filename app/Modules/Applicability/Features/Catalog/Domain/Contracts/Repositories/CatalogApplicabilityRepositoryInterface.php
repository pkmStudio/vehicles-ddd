<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\Contracts\Repositories;

use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicabilityEvidenceDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableCategoryDTO;
use App\Modules\Applicability\Features\Catalog\Domain\DTOs\ApplicableNomenclaturePageDTO;
use Illuminate\Support\Collection;

/**
 * Описывает read port применяемости для публичного каталога.
 */
interface CatalogApplicabilityRepositoryInterface
{
    /** Возвращает канонический артикул указанного бренда без учёта регистра. */
    public function findPartNumber(string $partNumber, int $brandId): ?string;

    /** Проверяет существование модификации по внутреннему primary key. */
    public function modificationExists(int $modificationId): bool;

    /** @return Collection<int, ApplicabilityEvidenceDTO> */
    public function evidence(string $partNumber, int $modificationId, int $brandId): Collection;

    /** @return Collection<int, ApplicableCategoryDTO> */
    public function categories(int $modificationId, int $brandId): Collection;

    /** Возвращает страницу применимых товаров или null для неизвестной/пустой категории. */
    public function paginateNomenclatures(
        int $modificationId,
        int $categoryId,
        int $brandId,
        int $page,
        int $pageSize,
    ): ?ApplicableNomenclaturePageDTO;
}
