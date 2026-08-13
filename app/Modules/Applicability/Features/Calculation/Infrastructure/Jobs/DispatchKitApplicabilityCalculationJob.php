<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Services\ApplicabilityCalculationRunProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

final class DispatchKitApplicabilityCalculationJob implements ShouldQueue
{
    use FoundationQueueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        private readonly ?int $kitId,
        private readonly int $chunk,
        private readonly string $operationId,
        private readonly int $userId,
    ) {}

    /**
     * Планирует chunked расчет применяемости.
     *
     * Шаги:
     * 1. Запоминает user id для внешнего notification по operation id.
     * 2. Читает активные kits через существующий Warehouse boundary и собирает их ids.
     * 3. Создает runtime-state расчета в cache и режет ids на чанки.
     * 4. Для пустого набора сразу ставит finalizer.
     * 5. Для каждого чанка dispatch-ит отдельную calculation job.
     */
    public function handle(
        WarehouseKitClientInterface $kits,
        ExternalCalculationContextServiceInterface $context,
        ApplicabilityCalculationRunProgress $progress,
    ): void {
        $context->rememberUserId($this->operationId, $this->userId);

        $kitIds = [];
        foreach ($kits->activeKits($this->kitId, $this->chunk) as $kit) {
            $kitIds[] = (int) $kit->id;
        }

        $chunks = array_chunk($kitIds, max(1, $this->chunk));
        $progress->startRun(
            operationId: $this->operationId,
            userId: $this->userId,
            kitId: $this->kitId,
            chunkSize: max(1, $this->chunk),
            chunks: $chunks,
        );

        if ($chunks === [] && $progress->requestFinalization($this->operationId)) {
            FinalizeKitApplicabilityCalculationJob::dispatch($this->operationId);

            return;
        }

        foreach ($progress->chunks($this->operationId) as $index => $kitIds) {
            CalculateKitApplicabilityChunkJob::dispatch(
                operationId: $this->operationId,
                chunkIndex: (int) $index,
                kitIds: $kitIds,
            );
        }
    }
}
