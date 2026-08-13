<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\FinalizeKitApplicabilityCalculationJob;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Services\ApplicabilityCalculationRunProgress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class FinalizeKitApplicabilityCalculationJobTest extends TestCase
{
    public function test_dispatches_result_event_and_cleans_runtime_cache_after_finalization(): void
    {
        Event::fake([KitApplicabilityRecalculated::class]);
        Queue::fake();
        $progress = app(ApplicabilityCalculationRunProgress::class);
        $progress->startRun(
            operationId: 'calculation-success',
            userId: 42,
            kitId: null,
            chunkSize: 100,
            chunks: [[10, 11]],
        );
        $progress->completeChunk(
            operationId: 'calculation-success',
            chunkIndex: 0,
            result: new KitApplicabilityCalculationResultDTO(
                operationId: 'calculation-success',
                processedKits: 2,
                calculatedKits: 2,
                affectedKitIds: [10, 11],
            ),
        );

        (new FinalizeKitApplicabilityCalculationJob('calculation-success'))->handle($progress);

        Event::assertDispatched(
            KitApplicabilityRecalculated::class,
            fn (KitApplicabilityRecalculated $event): bool => $event->operationId === 'calculation-success'
                && $event->result->processedKits === 2
                && $event->result->calculatedKits === 2
                && $event->result->affectedKitIds === [10, 11],
        );
        $this->assertNull($progress->result('calculation-success'));
    }

    public function test_chunk_completion_is_idempotent(): void
    {
        Event::fake([KitApplicabilityRecalculated::class]);
        Queue::fake();
        $progress = app(ApplicabilityCalculationRunProgress::class);
        $progress->startRun(
            operationId: 'calculation-idempotent',
            userId: 42,
            kitId: null,
            chunkSize: 100,
            chunks: [[10]],
        );

        $result = new KitApplicabilityCalculationResultDTO(
            operationId: 'calculation-idempotent',
            processedKits: 1,
            calculatedKits: 1,
            affectedKitIds: [10],
        );

        $progress->completeChunk('calculation-idempotent', 0, $result);
        $progress->completeChunk('calculation-idempotent', 0, $result);

        $aggregate = $progress->result('calculation-idempotent');

        $this->assertNotNull($aggregate);
        $this->assertSame(1, $aggregate->processedKits);
        $this->assertSame(1, $aggregate->calculatedKits);
        $this->assertSame([10], $aggregate->affectedKitIds);
    }

    public function test_failed_chunk_is_aggregated_for_final_result_and_cache_is_cleaned(): void
    {
        Event::fake([KitApplicabilityRecalculated::class]);
        Queue::fake();
        $progress = app(ApplicabilityCalculationRunProgress::class);
        $progress->startRun(
            operationId: 'calculation-with-failures',
            userId: 42,
            kitId: null,
            chunkSize: 100,
            chunks: [[10], [11]],
        );
        $progress->completeChunk(
            operationId: 'calculation-with-failures',
            chunkIndex: 0,
            result: new KitApplicabilityCalculationResultDTO(
                operationId: 'calculation-with-failures',
                processedKits: 1,
                calculatedKits: 1,
            ),
        );
        $progress->failChunk('calculation-with-failures', 1, 'Chunk failed');

        (new FinalizeKitApplicabilityCalculationJob('calculation-with-failures'))->handle($progress);

        Event::assertDispatched(
            KitApplicabilityRecalculated::class,
            fn (KitApplicabilityRecalculated $event): bool => $event->operationId === 'calculation-with-failures'
                && $event->result->processedKits === 1
                && $event->result->failedKits === 1
                && $event->result->errors === ['Chunk failed'],
        );
        $this->assertNull($progress->result('calculation-with-failures'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }
}
