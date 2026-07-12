<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\PartSpecification;

use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Передает данные автомобиля-владельца, если его нужно создать или обновить для спеки.
 */
final readonly class PartSpecificationOwnerVehicleDTO
{
    /**
     * Инициализирует immutable-снимок данных автомобиля-владельца.
     */
    public function __construct(
        public int $mfaId,
        public string $name,
        public VehicleTypeEnum $type,
        public CarcaseTypeEnum $typeCarcase,
        public ProviderEnum $provider,
        public SteeringTypeEnum $steeringType,
        public ?int $parentMsId = null,
        public ?string $generation = null,
        public ?string $generationShort = null,
        public ?string $localizedName = null,
        public ?string $excelTableId = null,
        public ?int $generationYearFrom = null,
        public ?int $generationYearTo = null,
        public bool $isAllow = false,
    ) {}
}
