<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\DTOs;

/**
 * Исход импорта спецификации свечей для двигателей модификации — адаптер транслирует его в отчёт.
 */
final readonly class ModificationSparkPlugResult
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

    public static function notFound(string $reason): self
    {
        return new self(found: false, notFoundReason: $reason);
    }

    /**
     * @param  array<int, array{code: ?string, fuel: ?string}>  $skipped
     */
    public static function written(int $count, array $skipped): self
    {
        return new self(found: true, writtenCount: $count, skippedEngines: $skipped);
    }
}
