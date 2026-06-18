<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Application\UseCases\Import\ReportImportResult;
use App\Vehicles\Domain\Events\AbstractImportCompleted;

final readonly class ReportImportResultListener
{
    public function __construct(
        private ReportImportResult $useCase,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        $this->useCase->handle($event->user, $event->cacheKey);
    }
}
