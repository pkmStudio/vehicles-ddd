<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\DTOs\Kit;

/**
 * Валидированная строка импорта Warehouse-набора.
 */
final readonly class KitImportRowDTO
{
    /**
     * @param  array<int, string>  $partNumbers
     */
    public function __construct(
        public ?int $id,
        public array $partNumbers,
        public bool $isSaleSeparately,
        public bool $isActive,
    ) {}
}
