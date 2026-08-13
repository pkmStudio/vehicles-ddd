<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs;

use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Services\ApplicabilityCalculationRunProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

final class FinalizeKitApplicabilityCalculationJob implements ShouldQueue
{
    use FoundationQueueable;

    public int $timeout = 120;

    public int $tries = 3;

    public function __construct(
        private readonly string $operationId,
    ) {}

    /**
     * Завершает chunked расчет и очищает runtime cache-state.
     *
     * Шаги:
     * 1. Забирает aggregate result из cache coordinator.
     * 2. Завершает job без действий, если state уже очищен.
     * 3. Публикует единый `KitApplicabilityRecalculated` для notification/report listener-а.
     * 4. Удаляет runtime cache-ключи расчета после публикации результата.
     */
    public function handle(ApplicabilityCalculationRunProgress $progress): void
    {
        $result = $progress->result($this->operationId);

        if ($result === null) {
            return;
        }

        event(new KitApplicabilityRecalculated($this->operationId, $result));
        $progress->forget($this->operationId);
    }
}
