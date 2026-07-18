<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\DTOs\Engine;

/**
 * Исход импорта спецификации свечей для двигателей модификации — адаптер транслирует его в отчёт.
 */
final readonly class ModificationSparkPlugResultDTO
{
    /**
     * @param  array<int, array{code: ?string, fuel: ?string}>  $skippedEngines  двигатели без потребности в свечах
     */
    public function __construct(
        public bool $found,
        public int $writtenCount = 0,
        public array $skippedEngines = [],
        public ?string $notFoundReason = null,
    ) {}

}
