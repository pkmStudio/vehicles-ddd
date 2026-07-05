<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\DTOs;

use App\Vehicles\Import\Domain\Enums\InOut\Sheets\VehicleImportSheet;

/**
 * Политика импорта для vehicle: какие листы нужно обрабатывать.
 */
final readonly class VehicleImportPlan
{
    /**
     * @param VehicleImportSheet[] $sheets
     */
    public function __construct(
        public array $sheets,
    ) {}

    public static function all(): self
    {
        return new self(
            sheets: [
                VehicleImportSheet::Main,
                VehicleImportSheet::Wipers,
            ],
        );
    }

    public static function mainOnly(): self
    {
        return new self(
            sheets: [
                VehicleImportSheet::Main,
            ],
        );
    }

    public function hasSheet(VehicleImportSheet $sheet): bool
    {
        return in_array($sheet, $this->sheets, true);
    }
}
