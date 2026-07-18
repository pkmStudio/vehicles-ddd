<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Хранит типизированный снимок записи модификаций для Repository и Command.
 */
#[MapName(SnakeCaseMapper::class)]
final class ModificationData extends Data
{
    /**
     * Инициализирует immutable-снимок данных модификаций.
     */
    public function __construct(
        public readonly int $modId,
        public readonly VehicleTypeEnum $type,
        public readonly int $vehicleId,
        public readonly int $msId,
        public readonly ?int $yearFrom = null,
        public readonly ?int $yearTo = null,
        public readonly ?string $description = null,
        public readonly ?int $powerPs = null,
        public readonly ?int $powerKw = null,
        public readonly ?EngineTypeEnum $engineType = null,
        public readonly ?GearTypeEnum $gearType = null,
        public readonly ?DriveTypeEnum $driveType = null,
        public readonly ?BrakeSystemTypeEnum $brakeSystemType = null,
        public readonly ?int $numberOfCylinders = null,
        public readonly ?float $capacityLt = null,
        public readonly ?int $id = null,
    ) {}
}
