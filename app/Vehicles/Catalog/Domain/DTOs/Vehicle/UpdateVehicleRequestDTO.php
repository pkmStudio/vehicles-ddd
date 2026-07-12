<?php

declare(strict_types=1);

namespace App\Vehicles\Catalog\Domain\DTOs\Vehicle;

use App\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class UpdateVehicleRequestDTO
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public int $msId,
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
