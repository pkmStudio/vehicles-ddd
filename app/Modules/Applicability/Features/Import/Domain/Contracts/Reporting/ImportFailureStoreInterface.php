<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting;

interface ImportFailureStoreInterface
{
    public function pull(string $cacheKey): array;
}
