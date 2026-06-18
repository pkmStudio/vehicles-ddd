<?php

declare(strict_types=1);

namespace App\Vehicles\Application\DTOs\Vehicle;

final readonly class VehicleData
{
    public function __construct(
        public int $msId,
        public int $mfaId,
        public int $manufacturerId,
        public string $name,
        public string $type,
        public string $steeringType,
        public ?string $generation = null,
        public ?string $typeCarcase = null,
        public ?int $generationYearFrom = null,
        public ?int $generationYearTo = null,
        public string $provider = 'TD',
        public ?int $parentId = null,
        public ?string $excelTableId = null,
        public ?string $localizedName = null,
        public ?string $generationShort = null,
        public bool $isAllow = false,
    ) {}

    public function toArray(): array
    {
        return [
            'ms_id' => $this->msId,
            'mfa_id' => $this->mfaId,
            'manufacturer_id' => $this->manufacturerId,
            'name' => $this->name,
            'type' => $this->type,
            'steering_type' => $this->steeringType,
            'generation' => $this->generation,
            'type_carcase' => $this->typeCarcase,
            'generation_year_from' => $this->generationYearFrom,
            'generation_year_to' => $this->generationYearTo,
            'provider' => $this->provider,
            'parent_id' => $this->parentId,
            'excel_table_id' => $this->excelTableId,
            'localized_name' => $this->localizedName,
            'generation_short' => $this->generationShort,
            'is_allow' => $this->isAllow,
        ];
    }
}
