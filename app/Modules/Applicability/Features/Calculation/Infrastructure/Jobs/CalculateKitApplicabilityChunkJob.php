<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs;

use App\Modules\Applicability\Features\Calculation\Application\UseCases\CalculateKitApplicabilityUseCase;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Services\ApplicabilityCalculationRunProgress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Throwable;

final class CalculateKitApplicabilityChunkJob implements ShouldQueue
{
    use FoundationQueueable;

    public int $timeout = 120;

    public int $tries = 1;

    /**
     * @param  array<int, int>  $kitIds
     */
    public function __construct(
        private readonly string $operationId,
        private readonly int $chunkIndex,
        private readonly array $kitIds,
    ) {}

    /**
     * Считает один chunk комплектов и добавляет его aggregate result к runtime-state.
     *
     * Шаги:
     * 1. Нормализует ids комплекта из serialized job payload.
     * 2. Для каждого kit id вызывает существующий use case без промежуточного result event.
     * 3. Складывает counters, affected kit ids и errors внутри chunk-а.
     * 4. Передает aggregate result чанка в cache progress coordinator.
     */
    public function handle(
        CalculateKitApplicabilityUseCase $useCase,
        ApplicabilityCalculationRunProgress $progress,
    ): void {
        $processed = 0;
        $calculated = 0;
        $skipped = 0;
        $failed = 0;
        $affectedKitIds = [];
        $errors = [];

        foreach (array_map(static fn (int|string $id): int => (int) $id, $this->kitIds) as $kitId) {
            $result = $useCase->execute(
                kitId: $kitId,
                chunk: 1,
                operationId: $this->operationId,
                dispatchResultEvent: false,
            );

            $processed += $result->processedKits;
            $calculated += $result->calculatedKits;
            $skipped += $result->skippedKits;
            $failed += $result->failedKits;
            $affectedKitIds = array_merge($affectedKitIds, $result->affectedKitIds);
            $errors = array_merge($errors, $result->errors);
        }

        $progress->completeChunk(
            operationId: $this->operationId,
            chunkIndex: $this->chunkIndex,
            result: new KitApplicabilityCalculationResultDTO(
                operationId: $this->operationId,
                processedKits: $processed,
                calculatedKits: $calculated,
                skippedKits: $skipped,
                failedKits: $failed,
                affectedKitIds: array_values(array_unique($affectedKitIds)),
                errors: $errors,
            ),
        );
    }

    /**
     * Фиксирует падение chunk job в runtime-state расчета.
     *
     * Шаги:
     * 1. Берет сообщение exception или fallback text.
     * 2. Передает ошибку в progress coordinator.
     * 3. Coordinator сам решает, нужно ли запускать finalizer.
     */
    public function failed(?Throwable $exception): void
    {
        app(ApplicabilityCalculationRunProgress::class)->failChunk(
            operationId: $this->operationId,
            chunkIndex: $this->chunkIndex,
            error: $exception?->getMessage() ?? 'Calculation chunk failed without exception message',
        );
    }
}
