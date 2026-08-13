<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine;

final readonly class EngineSheetRowDTO
{
    /**
     * Фиксирует нормализованную строку Excel-листа двигателей.
     */
    public function __construct(
        public ?int $engId,
        public ?string $codeEngine,
        public ?int $powerKwStart,
        public ?int $powerKwUpto,
        public ?int $powerPsStart,
        public ?int $powerPsUpto,
        public ?string $engineCapacity,
        public ?float $cylinderDiameter,
        public ?int $cylinderCount,
        public ?int $numberOfValves,
        public ?string $fuelType,
    ) {}
}
