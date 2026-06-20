<?php

declare(strict_types=1);

namespace App\Vehicles\Domain\Contracts\Application\Import\UseCases\Reporting;

interface ReportImportResultUseCaseInterface
{
    public function execute(int $userId, string $cacheKey): void;
}
