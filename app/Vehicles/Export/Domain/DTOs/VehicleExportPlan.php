<?php

declare(strict_types=1);

namespace App\Vehicles\Export\Domain\DTOs;

use App\Vehicles\Export\Domain\Enums\InOut\Sheets\VehicleExportSheet;

/**
 * Политика экспорта для vehicle-экспорта: какие листы нужно включить и какой флаг is_allow использовать.
 */
final readonly class VehicleExportPlan
{
    /**
     * @param VehicleExportSheet[] $sheets
     */
    public function __construct(
        public bool $isAllow,
        public array $sheets,
    ) {}

    public static function all(bool $isAllow = false): self
    {
        return new self(
            isAllow: $isAllow,
            sheets: [
                VehicleExportSheet::Main,
                VehicleExportSheet::Wipers,
            ],
        );
    }

    public static function mainOnly(bool $isAllow = false): self
    {
        return new self(
            isAllow: $isAllow,
            sheets: [
                VehicleExportSheet::Main,
            ],
        );
    }

    public function hasSheet(VehicleExportSheet $sheet): bool
    {
        return in_array($sheet, $this->sheets, true);
    }
}
