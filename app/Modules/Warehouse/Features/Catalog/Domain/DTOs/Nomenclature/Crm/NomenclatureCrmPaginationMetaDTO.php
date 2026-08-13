<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm;

/**
 * Meta-снимок пагинации CRM списка Warehouse-номенклатуры.
 */
final readonly class NomenclatureCrmPaginationMetaDTO
{
    /**
     * Фиксирует значения DTO без дополнительного поведения.
     */
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
    ) {}

    /**
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage,
        ];
    }
}
