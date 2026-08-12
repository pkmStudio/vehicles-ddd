<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Vehicle;

final readonly class VehicleWiperSheetRowDTO
{
    /**
     * Фиксирует строку wiper specification листа для vehicle workbook.
     */
    public function __construct(
        public ?int $msId,
        public ?string $templateSlug,
        public ?string $featureValueName,
        public ?string $name,
        public ?string $text,
        public array $details,
    ) {}
}
