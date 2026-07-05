<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Services\Reporting;

interface ReportImportResultServiceInterface
{
    public function report(int $userId, string $cacheKey): void;
}
