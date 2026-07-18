<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\ModelData;

use App\Modules\Vehicles\Shared\Domain\Enums\Engine\EngineTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\BrakeSystemTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\DriveTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\GearTypeEnum;
use App\Modules\Vehicles\Shared\Domain\Enums\Vehicle\VehicleTypeEnum;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ModificationData extends Data
{
    /**
     * @param  Collection<int, EngineData>|null  $engines  двигатели модификации — заполняется
     *                                            только Repository::firstByMsIdAndModIdWithEngines()
     *                                            (с eager load), в остальных путях null и не
     *                                            участвует в записи через Command
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
        public readonly ?Collection $engines = null,
    ) {}
}
