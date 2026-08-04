<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Catalog\Domain\DTOs\Nomenclature\Crm;

/**
 * Option DTO справочников Warehouse-номенклатуры для CRM-форм.
 */
final readonly class NomenclatureCrmOptionDTO
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public int $id,
        public string $label,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            ...$this->meta,
        ];
    }
}
