<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases\CalculateKitApplicabilityUseCaseInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

final class CalculateKitApplicabilityJob implements ShouldQueue
{
    use FoundationQueueable;

    public function __construct(
        private readonly ?int $kitId = null,
        private readonly int $chunk = 1000,
        private readonly ?string $operationId = null,
        private readonly ?int $userId = null,
    ) {}

    public function handle(
        CalculateKitApplicabilityUseCaseInterface $useCase,
        ExternalCalculationContextServiceInterface $context,
    ): void {
        if ($this->operationId !== null && $this->userId !== null) {
            $context->rememberUserId($this->operationId, $this->userId);
        }

        $useCase->execute(
            kitId: $this->kitId,
            chunk: $this->chunk,
            operationId: $this->operationId,
        );
    }
}
