<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Import\Listeners;

use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Reporting\ReportImportResultUseCaseInterface;
use App\Vehicles\Domain\Events\AbstractImportCompleted;

final readonly class ReportImportResultListener
{
    public function __construct(
        private ReportImportResultUseCaseInterface $useCase,
    ) {}

    public function handle(AbstractImportCompleted $event): void
    {
        $this->useCase->execute($event->userId, $event->cacheKey);
    }
}
