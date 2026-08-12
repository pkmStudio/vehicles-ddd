<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\DTOs;

final readonly class KitApplicabilityImportRowDTO
{
    /**
     * Описывает валидированную строку XLSX применяемости: модель, модификация и комплект.
     */
    public function __construct(
        public int $msId,
        public int $modId,
        public int $kitId,
    ) {}
}
