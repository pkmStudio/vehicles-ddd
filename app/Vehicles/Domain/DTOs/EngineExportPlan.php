<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\DTOs;

use App\Vehicles\Domain\Enums\EngineExportSheet;

/**
 * Политика экспорта для engine-экспорта: какие листы нужно включить.
 */
final readonly class EngineExportPlan
{
    /**
     * @param EngineExportSheet[] $sheets
     */
    public function __construct(
        public array $sheets,
    ) {}

    public static function all(): self
    {
        return new self(
            sheets: [
                EngineExportSheet::Main,
                EngineExportSheet::SparkPlugs,
            ],
        );
    }

    public static function mainOnly(): self
    {
        return new self(
            sheets: [
                EngineExportSheet::Main,
            ],
        );
    }

    public function hasSheet(EngineExportSheet $sheet): bool
    {
        return in_array($sheet, $this->sheets, true);
    }
}
