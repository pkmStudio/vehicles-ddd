<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting;

interface ReportImportResultServiceInterface
{
    public function report(int $userId, string $cacheKey, ?string $runId = null): void;
}
