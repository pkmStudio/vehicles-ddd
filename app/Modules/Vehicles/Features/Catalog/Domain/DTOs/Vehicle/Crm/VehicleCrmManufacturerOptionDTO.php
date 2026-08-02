<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

/**
 * Manufacturer option для CRM-форм Vehicles.
 */
final readonly class VehicleCrmManufacturerOptionDTO
{
    /**
     * Хранит id, mfa id и label manufacturer option.
     */
    public function __construct(
        public int $id,
        public int $mfaId,
        public string $label,
    ) {}

    /**
     * Возвращает публичный manufacturer option payload CRM.
     *
     * @return array{id: int, mfa_id: int, label: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mfa_id' => $this->mfaId,
            'label' => $this->label,
        ];
    }
}
