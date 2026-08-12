<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle;

final readonly class VehicleTdRowDTO
{
    /**
     * Фиксирует строку TecDoc vehicle cascade без внутренних warehouse fields.
     */
    public function __construct(
        public ?int $mfaId,
        public ?int $msId,
        public ?string $name,
        public ?string $generation,
        public ?string $typeCarcase,
        public ?int $generationYearFrom,
        public ?int $generationYearTo,
        public ?string $type,
    ) {}
}
