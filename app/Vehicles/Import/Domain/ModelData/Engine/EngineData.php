<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\ModelData\Engine;

use App\Vehicles\Shared\Domain\Enums\Engine\EngineFuelTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class EngineData extends Data
{
    public function __construct(
        public readonly int $engId,
        public readonly ?string $codeEngine = null,
        public readonly ?int $engPowerKwStart = null,
        public readonly ?int $engPowerKwUpto = null,
        public readonly ?int $engPowerPsStart = null,
        public readonly ?int $engPowerPsUpto = null,
        public readonly ?string $engineCapacity = null,
        public readonly ?float $cylinderDiameter = null,
        public readonly ?int $cylinderCount = null,
        public readonly ?int $engNumberOfValves = null,
        public readonly ?EngineFuelTypeEnum $engFuelType = null,
        public readonly ?int $id = null,
        public readonly ?int $groupId = null,
    ) {}
}
