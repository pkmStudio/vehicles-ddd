<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Domain\DTOs;

/**
 * Строка export-а связи модификации и двигателя.
 */
final readonly class EngineModificationExportRowDTO
{
    /**
     * Фиксирует natural key связи `mod_id + eng_id + type`.
     */
    public function __construct(
        public int $modId,
        public int $engId,
        public string $type,
    ) {}
}
