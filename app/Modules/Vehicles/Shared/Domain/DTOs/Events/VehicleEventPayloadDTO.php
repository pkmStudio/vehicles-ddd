<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Shared\Domain\DTOs\Events;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

final readonly class VehicleEventPayloadDTO
{
    /**
     * Стабильный shared payload автомобиля для catalog facts.
     */
    public function __construct(
        public int $id,
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
    ) {}
}
