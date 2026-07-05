<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

abstract readonly class AbstractImportCompleted
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $cacheKey
    ) {}
}
