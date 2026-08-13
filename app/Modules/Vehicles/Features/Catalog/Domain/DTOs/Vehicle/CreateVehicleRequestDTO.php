<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\DTOs\Vehicle;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;

/**
 * Передает параметры сценария или результат мутации автомобилей.
 */
final readonly class CreateVehicleRequestDTO
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public int $userId,
        public string $operationId,
        public ?int $msId,
        public int $mfaId,
        public string $name,
        public VehicleTypeEnum $type,
        public CarcaseTypeEnum $typeCarcase,
        public ProviderEnum $provider,
        public SteeringTypeEnum $steeringType,
        public string $generation,
        public int $generationYearFrom,
        public ?int $parentMsId = null,
        public ?string $generationShort = null,
        public ?string $localizedName = null,
        public ?string $excelTableId = null,
        public ?int $generationYearTo = null,
        public bool $isAllow = false,
    ) {}
}
