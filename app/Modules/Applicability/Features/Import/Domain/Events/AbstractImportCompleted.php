<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Events;

abstract readonly class AbstractImportCompleted
{
    /**
     * Хранит общий контекст события завершения import-а для reporting и cleanup listeners.
     */
    public function __construct(
        public ?int $userId,
        public string $cacheKey,
        public ?string $operationId,
    ) {}
}
