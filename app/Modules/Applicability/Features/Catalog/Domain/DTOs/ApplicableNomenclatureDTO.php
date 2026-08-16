<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\DTOs;

/**
 * Краткая проекция применимого товара для frontend-каталога.
 */
final readonly class ApplicableNomenclatureDTO
{
    public function __construct(
        public string $partNumber,
        public string $name,
        public int $categoryId,
        public int $brandId,
        public string $brandName,
    ) {}

}
