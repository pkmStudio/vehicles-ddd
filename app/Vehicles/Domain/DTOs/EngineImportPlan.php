<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\DTOs;

use App\Vehicles\Domain\Enums\InOut\Sheets\EngineImportSheet;

/**
 * Политика импорта для engine: какие листы нужно обрабатывать.
 */
final readonly class EngineImportPlan
{
    /**
     * @param EngineImportSheet[] $sheets
     */
    public function __construct(
        public array $sheets,
    ) {}

    public static function all(): self
    {
        return new self(
            sheets: [
                EngineImportSheet::Main,
                EngineImportSheet::SparkPlugs,
            ],
        );
    }

    public static function mainOnly(): self
    {
        return new self(
            sheets: [
                EngineImportSheet::Main,
            ],
        );
    }

    public function hasSheet(EngineImportSheet $sheet): bool
    {
        return in_array($sheet, $this->sheets, true);
    }
}
