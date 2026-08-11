<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

/**
 * Feature option для CRM-форм Vehicles.
 */
final readonly class VehicleCrmFeatureOptionDTO
{
    /**
     * Хранит id и label feature option.
     */
    public function __construct(
        public int $id,
        public string $label,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            label: (string) $data['label'],
        );
    }

    /**
     * Возвращает публичный feature option payload CRM.
     *
     * @return array{id: int, label: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
        ];
    }
}
