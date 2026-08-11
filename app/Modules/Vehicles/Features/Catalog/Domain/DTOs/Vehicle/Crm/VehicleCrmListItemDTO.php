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
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            parentMsId: isset($data['parent_ms_id']) ? (int) $data['parent_ms_id'] : null,
            manufacturerId: (int) $data['manufacturer_id'],
            manufacturerName: isset($data['manufacturer_name']) ? (string) $data['manufacturer_name'] : null,
            mfaId: (int) $data['mfa_id'],
            msId: (int) $data['ms_id'],
            name: (string) $data['name'],
            localizedName: isset($data['localized_name']) ? (string) $data['localized_name'] : null,
            excelTableId: isset($data['excel_table_id']) ? (string) $data['excel_table_id'] : null,
            generation: isset($data['generation']) ? (string) $data['generation'] : null,
            generationShort: isset($data['generation_short']) ? (string) $data['generation_short'] : null,
            generationYearFrom: isset($data['generation_year_from']) ? (int) $data['generation_year_from'] : null,
            generationYearTo: isset($data['generation_year_to']) ? (int) $data['generation_year_to'] : null,
            type: (string) $data['type'],
            typeCarcase: (string) $data['type_carcase'],
            provider: (string) $data['provider'],
            steeringType: (string) $data['steering_type'],
            isAllow: (bool) $data['is_allow'],
        );
    }

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
