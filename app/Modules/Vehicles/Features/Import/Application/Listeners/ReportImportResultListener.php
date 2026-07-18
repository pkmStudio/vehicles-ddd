<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Application\Listeners;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Reporting\ReportImportResultServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Events\AbstractImportCompleted;

final readonly class ReportImportResultListener
{
    public function __construct(
        private ReportImportResultServiceInterface $service,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        $this->service->report($event->userId, $event->cacheKey, $event->runId);
    }
}
