<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

/**
 * Detail template option для CRM-форм Vehicles.
 */
final readonly class VehicleCrmDetailTemplateOptionDTO
{
    /**
     * Хранит id и label detail template option.
     */
    public function __construct(
        public string $id,
        public string $label,
    ) {}

    /**
     * Возвращает публичный detail template option payload CRM.
     *
     * @return array{id: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
        ];
    }
}
