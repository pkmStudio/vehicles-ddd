<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases\CalculateKitApplicabilityUseCaseInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

final class CalculateKitApplicabilityJob implements ShouldQueue
{
    use FoundationQueueable;

    public function __construct(
        private readonly ?int $kitId = null,
        private readonly int $chunk = 1000,
        private readonly ?string $runId = null,
    ) {}

    public function handle(CalculateKitApplicabilityUseCaseInterface $useCase): void
    {
        $useCase->execute(
            kitId: $this->kitId,
            chunk: $this->chunk,
            runId: $this->runId,
        );
    }
}
