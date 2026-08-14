<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\CarcaseTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\SteeringTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Хранит типизированный снимок записи автомобилей для Repository и Command.
 */
#[MapName(SnakeCaseMapper::class)]
final class VehicleData extends Data
{
    /**
     * Инициализирует immutable-снимок данных автомобилей.
     */
    public function __construct(
        public readonly int $msId,
        public readonly int $mfaId,
        public readonly int $manufacturerId,
        public readonly string $name,
        public readonly VehicleTypeEnum $type,
        public readonly SteeringTypeEnum $steeringType,
        public readonly CarcaseTypeEnum $typeCarcase,
        public readonly ProviderEnum $provider,
        public readonly string $generation,
        public readonly int $generationYearFrom,
        public readonly ?int $generationYearTo = null,
        public readonly ?int $parentId = null,
        public readonly ?int $parentMsId = null,
        public readonly ?string $excelTableId = null,
        public readonly ?string $localizedName = null,
        public readonly ?string $generationShort = null,
        public readonly bool $isAllow = false,
        public readonly ?int $id = null,
    ) {}
}
