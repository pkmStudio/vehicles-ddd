<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm;

/**
 * Compact projection Warehouse-номенклатуры для CRM autocomplete.
 */
final readonly class NomenclatureCrmSearchItemDTO
{
    public function __construct(
        public int $id,
        public string $label,
        public string $partNumber,
    ) {}

    /**
     * @return array{id: int, label: string, part_number: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'part_number' => $this->partNumber,
        ];
    }
}
