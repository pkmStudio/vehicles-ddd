<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs\Engine;

final readonly class EngineSheetRowDTO
{
    public function __construct(
        public ?int $engId,
        public ?string $codeEngine,
        public ?int $engPowerKwStart,
        public ?int $engPowerKwUpto,
        public ?int $engPowerPsStart,
        public ?int $engPowerPsUpto,
        public ?string $engineCapacity,
        public ?float $cylinderDiameter,
        public ?int $cylinderCount,
        public ?int $engNumberOfValves,
        public ?string $engFuelType,
    ) {}
}
