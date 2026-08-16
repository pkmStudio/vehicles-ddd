<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Catalog\Domain\DTOs;

/**
 * Категория Warehouse с товарами, применимыми к выбранной модификации.
 */
final readonly class ApplicableCategoryDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public int $nomenclatureCount,
    ) {}

}
