<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle\Crm;

/**
 * Сценарный снимок автомобиля для списка CRM read API.
 */
final readonly class VehicleCrmListItemDTO
{
    /**
     * Хранит плоские поля автомобиля, производителя и родительской модели для CRM списка.
     */
    public function __construct(
        public int $id,
        public ?int $parentId,
        public ?int $parentMsId,
        public int $manufacturerId,
        public ?string $manufacturerName,
        public int $mfaId,
        public int $msId,
        public string $name,
        public ?string $localizedName,
        public ?string $excelTableId,
        public ?string $generation,
        public ?string $generationShort,
        public ?int $generationYearFrom,
        public ?int $generationYearTo,
        public string $type,
        public string $typeCarcase,
        public string $provider,
        public string $steeringType,
        public bool $isAllow,
    ) {}

    /**
     * Возвращает публичный payload CRM read API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'parent_ms_id' => $this->parentMsId,
            'manufacturer_id' => $this->manufacturerId,
            'manufacturer_name' => $this->manufacturerName,
            'mfa_id' => $this->mfaId,
            'ms_id' => $this->msId,
            'name' => $this->name,
            'localized_name' => $this->localizedName,
            'excel_table_id' => $this->excelTableId,
            'generation' => $this->generation,
            'generation_short' => $this->generationShort,
            'generation_year_from' => $this->generationYearFrom,
            'generation_year_to' => $this->generationYearTo,
            'type' => $this->type,
            'type_carcase' => $this->typeCarcase,
            'provider' => $this->provider,
            'steering_type' => $this->steeringType,
            'is_allow' => $this->isAllow,
        ];
    }
}
