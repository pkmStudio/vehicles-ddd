<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\ProviderEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Хранит типизированный снимок модификации для Excel export.
 */
#[MapName(SnakeCaseMapper::class)]
final class ModificationData extends Data
{
    /**
     * Инициализирует immutable-снимок export-строки модификации.
     */
    public function __construct(
        public readonly int $msId,
        public readonly int $modId,
        public readonly VehicleTypeEnum $type,
        public readonly ProviderEnum $provider,
        public readonly int $yearFrom,
        public readonly string $description,
        public readonly int $powerPs,
        public readonly int $powerKw,
        public readonly EngineTypeEnum $engineType,
        public readonly ?string $localizedName = null,
        public readonly ?int $yearTo = null,
        public readonly ?float $capacityLt = null,
        public readonly ?DriveTypeEnum $driveType = null,
        public readonly ?GearTypeEnum $gearType = null,
        public readonly ?BrakeSystemTypeEnum $brakeSystemType = null,
        public readonly ?int $numberOfCylinders = null,
        public readonly ?string $descriptionShort = null,
        public readonly ?int $id = null,
    ) {}
}
