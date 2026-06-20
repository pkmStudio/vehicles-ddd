<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Listeners;

use App\Vehicles\Application\Import\UseCases\Reporting\ReportImportResultUseCase;
use App\Vehicles\Domain\Events\AbstractImportCompleted;

final readonly class ReportImportResultListener
{
    public function __construct(
        private ReportImportResultUseCase $useCase,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        $this->useCase->execute($event->userId, $event->cacheKey);
    }
}
