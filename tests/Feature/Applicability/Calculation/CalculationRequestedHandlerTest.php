<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases\CalculateKitApplicabilityUseCaseInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\CalculateKitApplicabilityChunkJob;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\CalculateKitApplicabilityJob;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\DispatchKitApplicabilityCalculationJob;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Handlers\CalculationRequestedHandler;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Services\ApplicabilityCalculationRunProgress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Calculation\DTO\CalculationRequested as WireCalculationRequested;
use ReflectionClass;
use Tests\TestCase;

final class CalculationRequestedHandlerTest extends TestCase
{
    public function test_dispatches_calculation_job_from_valid_payload(): void
    {
        Queue::fake();

        app(CalculationRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'operation-123',
            'kit_id' => 7,
            'chunk' => 50,
        ]);

        Queue::assertPushed(DispatchKitApplicabilityCalculationJob::class, function (DispatchKitApplicabilityCalculationJob $job): bool {
            return $this->jobProperty($job, 'userId') === 42
                && $this->jobProperty($job, 'operationId') === 'operation-123'
                && $this->jobProperty($job, 'kitId') === 7
                && $this->jobProperty($job, 'chunk') === 50;
        });
    }

    public function test_accepts_published_wire_calculation_request_payload(): void
    {
        Queue::fake();

        $message = new WireCalculationRequested(
            userId: 42,
            operationId: 'wire-calculate-applicability',
            kitId: 7,
            chunk: 50,
        );

        app(CalculationRequestedHandler::class)->handle($message->toArray());

        Queue::assertPushed(DispatchKitApplicabilityCalculationJob::class, function (DispatchKitApplicabilityCalculationJob $job): bool {
            return $this->jobProperty($job, 'userId') === 42
                && $this->jobProperty($job, 'operationId') === 'wire-calculate-applicability'
                && $this->jobProperty($job, 'kitId') === 7
                && $this->jobProperty($job, 'chunk') === 50;
        });
    }

    public function test_invalid_payload_is_logged_and_skipped(): void
    {
        Queue::fake();

        Log::shouldReceive('error')
            ->once()
            ->with(
                'RabbitMQ: Applicability calculation request payload validation failed',
                Mockery::on(fn (array $context): bool => in_array('operation_id', $context['invalid_keys'] ?? [], true)),
            );

        app(CalculationRequestedHandler::class)->handle([
            'user_id' => 42,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_job_passes_payload_to_use_case(): void
    {
        $useCase = Mockery::mock(CalculateKitApplicabilityUseCaseInterface::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(7, 50, 'operation-123')
            ->andReturn(new KitApplicabilityCalculationResultDTO(operationId: 'operation-123'));

        $context = Mockery::mock(ExternalCalculationContextServiceInterface::class);
        $context->shouldReceive('rememberUserId')
            ->once()
            ->with('operation-123', 42);

        (new CalculateKitApplicabilityJob(
            kitId: 7,
            chunk: 50,
            operationId: 'operation-123',
            userId: 42,
        ))->handle($useCase, $context);

        $this->addToAssertionCount(1);
    }

    public function test_chunk_job_aggregates_existing_use_case_results_without_intermediate_events(): void
    {
        Queue::fake();
        Cache::flush();

        $progress = app(ApplicabilityCalculationRunProgress::class);
        $progress->startRun(
            operationId: 'operation-chunk',
            userId: 42,
            kitId: null,
            chunkSize: 2,
            chunks: [[7, 8]],
        );

        $useCase = Mockery::mock(CalculateKitApplicabilityUseCaseInterface::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(7, 1, 'operation-chunk', false)
            ->andReturn(new KitApplicabilityCalculationResultDTO(
                operationId: 'operation-chunk',
                processedKits: 1,
                calculatedKits: 1,
                affectedKitIds: [7],
            ));
        $useCase->shouldReceive('execute')
            ->once()
            ->with(8, 1, 'operation-chunk', false)
            ->andReturn(new KitApplicabilityCalculationResultDTO(
                operationId: 'operation-chunk',
                processedKits: 1,
                skippedKits: 1,
            ));

        (new CalculateKitApplicabilityChunkJob(
            operationId: 'operation-chunk',
            chunkIndex: 0,
            kitIds: [7, 8],
        ))->handle($useCase, $progress);

        $result = $progress->result('operation-chunk');

        $this->assertNotNull($result);
        $this->assertSame(2, $result->processedKits);
        $this->assertSame(1, $result->calculatedKits);
        $this->assertSame(1, $result->skippedKits);
        $this->assertSame([7], $result->affectedKitIds);
    }

    public function test_calculation_request_event_is_registered(): void
    {
        $this->assertSame(
            [CalculationRequestedHandler::class, 'handle'],
            config('rabbit-transport.inbound.APPLICABILITY_CALCULATION_REQUESTED'),
        );

        $this->assertContains('crm.applicability.calculate', (array) config('rabbit-transport.setup.bindings'));
        $this->assertSame(
            'applicability.calculation.completed',
            config('rabbit-transport.outbound.APPLICABILITY_CALCULATION_COMPLETED'),
        );
    }

    private function jobProperty(object $job, string $property): mixed
    {
        $reflection = new ReflectionClass($job);

        return $reflection->getProperty($property)->getValue($job);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();

        parent::tearDown();
    }
}
