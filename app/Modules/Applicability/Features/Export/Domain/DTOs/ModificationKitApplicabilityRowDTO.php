<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Domain\DTOs;

final readonly class ModificationKitApplicabilityRowDTO
{
    /**
     * Описывает строку XLSX применяемости комплекта к модификации.
     */
    public function __construct(
        public int $msId,
        public int $modId,
        public int $kitId,
    ) {}
}
