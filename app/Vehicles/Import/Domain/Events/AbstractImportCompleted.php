<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Events;

abstract readonly class AbstractImportCompleted
{
    public function __construct(
        public int $userId,
        public string $cacheKey
    ) {}
}
