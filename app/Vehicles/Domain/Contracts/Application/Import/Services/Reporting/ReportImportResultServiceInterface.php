<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\Services\Reporting;

interface ReportImportResultServiceInterface
{
    public function report(int $userId, string $cacheKey): void;
}
