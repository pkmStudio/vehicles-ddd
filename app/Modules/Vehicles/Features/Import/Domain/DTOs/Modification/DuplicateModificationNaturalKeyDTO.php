<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Modification;

/**
 * Описывает дубль natural key модификации `mod_id + type`.
 */
final readonly class DuplicateModificationNaturalKeyDTO
{
    /**
     * Фиксирует natural key и количество найденных строк.
     */
    public function __construct(
        public int $modId,
        public string $type,
        public int $count,
    ) {}
}
