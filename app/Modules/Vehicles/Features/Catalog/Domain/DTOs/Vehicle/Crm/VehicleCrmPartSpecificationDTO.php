<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

/**
 * Сценарный снимок спецификации детали для CRM detail projection автомобиля.
 */
final readonly class VehicleCrmPartSpecificationDTO
{
    /**
     * Хранит спецификацию детали и связанные feature fields для CRM detail ответа.
     *
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public int $id,
        public string $partableType,
        public int $partableId,
        public ?int $featureId,
        public ?string $featureName,
        public ?int $featureValueId,
        public ?string $featureValueName,
        public ?string $featureValueShortCode,
        public string $template,
        public ?string $name,
        public ?string $text,
        public array $details,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * Возвращает публичный payload спецификации детали для CRM detail ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'partable_type' => $this->partableType,
            'partable_id' => $this->partableId,
            'feature_id' => $this->featureId,
            'feature_name' => $this->featureName,
            'feature_value_id' => $this->featureValueId,
            'feature_value_name' => $this->featureValueName,
            'feature_value_short_code' => $this->featureValueShortCode,
            'template' => $this->template,
            'name' => $this->name,
            'text' => $this->text,
            'details' => $this->details,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
