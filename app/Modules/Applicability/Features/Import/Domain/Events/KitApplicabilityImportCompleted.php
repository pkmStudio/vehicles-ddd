<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Events;

final readonly class KitApplicabilityImportCompleted
{
    public function __construct(
        public ?int $userId,
        public ?string $cacheKey,
        public ?string $runId,
    ) {}
}
