<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Listeners;

use App\Vehicles\Domain\Contracts\Application\Import\Services\Reporting\ReportImportResultServiceInterface;
use App\Vehicles\Domain\Events\AbstractImportCompleted;

final readonly class ReportImportResultListener
{
    public function __construct(
        private ReportImportResultServiceInterface $service,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        $this->service->report($event->userId, $event->cacheKey);
    }
}
