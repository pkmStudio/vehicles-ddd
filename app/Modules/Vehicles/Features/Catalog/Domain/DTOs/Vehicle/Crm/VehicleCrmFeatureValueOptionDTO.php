<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

/**
 * Feature value option для CRM-форм Vehicles.
 */
final readonly class VehicleCrmFeatureValueOptionDTO
{
    /**
     * Хранит id, feature id, label и short code feature value option.
     */
    public function __construct(
        public int $id,
        public int $featureId,
        public string $label,
        public string $shortCode,
    ) {}

    /**
     * Возвращает публичный feature value option payload CRM.
     *
     * @return array{id: int, feature_id: int, label: string, short_code: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'feature_id' => $this->featureId,
            'label' => $this->label,
            'short_code' => $this->shortCode,
        ];
    }
}
