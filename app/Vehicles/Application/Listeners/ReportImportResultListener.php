<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Listeners;

use App\Vehicles\Application\UseCases\Import\ReportImportResultUseCase;
use App\Vehicles\Domain\Events\AbstractImportCompleted;

final readonly class ReportImportResultListener
{
    public function __construct(
        private ReportImportResultUseCase $useCase,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        $this->useCase->execute($event->user, $event->cacheKey);
    }
}
