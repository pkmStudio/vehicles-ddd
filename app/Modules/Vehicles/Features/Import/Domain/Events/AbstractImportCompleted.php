<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Events;

abstract readonly class AbstractImportCompleted
{
    /**
     * Фиксирует адресата отчета, cache key failures и optional operation correlation id.
     */
    public function __construct(
        public int $userId,
        public string $cacheKey,
        public ?string $operationId = null,
    ) {}
}
