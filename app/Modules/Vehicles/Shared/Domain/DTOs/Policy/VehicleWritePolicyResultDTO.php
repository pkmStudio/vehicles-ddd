<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Policy;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Общий снимок автомобиля для provider-aware write policy.
 */
final readonly class VehicleWritePolicyResultDTO
{
    /**
     * Фиксирует результат применения правил записи автомобиля.
     */
    public function __construct(
        public int $msId,
        public int $mfaId,
        public int $manufacturerId,
        public string $name,
        public VehicleTypeEnum $type,
        public SteeringTypeEnum $steeringType,
        public CarcaseTypeEnum $typeCarcase,
        public ProviderEnum $provider,
        public string $generation,
        public int $generationYearFrom,
        public bool $isAllow,
        public ?int $generationYearTo = null,
        public ?int $parentId = null,
        public ?int $parentMsId = null,
        public ?string $excelTableId = null,
        public ?string $localizedName = null,
        public ?string $generationShort = null,
        public ?int $id = null,
    ) {}

    /**
     * Собирает DTO из snake_case массива локального Data-снимка.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            msId: (int) $payload['ms_id'],
            mfaId: (int) $payload['mfa_id'],
            manufacturerId: (int) $payload['manufacturer_id'],
            name: (string) $payload['name'],
            type: VehicleTypeEnum::from($payload['type']),
            steeringType: SteeringTypeEnum::from($payload['steering_type']),
            typeCarcase: CarcaseTypeEnum::from($payload['type_carcase']),
            provider: ProviderEnum::from($payload['provider']),
            generation: (string) $payload['generation'],
            generationYearFrom: (int) $payload['generation_year_from'],
            isAllow: (bool) $payload['is_allow'],
            generationYearTo: isset($payload['generation_year_to']) ? (int) $payload['generation_year_to'] : null,
            parentId: isset($payload['parent_id']) ? (int) $payload['parent_id'] : null,
            parentMsId: isset($payload['parent_ms_id']) ? (int) $payload['parent_ms_id'] : null,
            excelTableId: isset($payload['excel_table_id']) ? (string) $payload['excel_table_id'] : null,
            localizedName: isset($payload['localized_name']) ? (string) $payload['localized_name'] : null,
            generationShort: isset($payload['generation_short']) ? (string) $payload['generation_short'] : null,
            id: isset($payload['id']) ? (int) $payload['id'] : null,
        );
    }

    /**
     * Возвращает snake_case массив для передачи в feature-local Spatie Data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ms_id' => $this->msId,
            'mfa_id' => $this->mfaId,
            'manufacturer_id' => $this->manufacturerId,
            'name' => $this->name,
            'type' => $this->type->value,
            'steering_type' => $this->steeringType->value,
            'type_carcase' => $this->typeCarcase->value,
            'provider' => $this->provider->value,
            'generation' => $this->generation,
            'generation_year_from' => $this->generationYearFrom,
            'generation_year_to' => $this->generationYearTo,
            'parent_id' => $this->parentId,
            'parent_ms_id' => $this->parentMsId,
            'excel_table_id' => $this->excelTableId,
            'localized_name' => $this->localizedName,
            'generation_short' => $this->generationShort,
            'is_allow' => $this->isAllow,
            'id' => $this->id,
        ];
    }
}
