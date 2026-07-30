<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases\CalculateKitApplicabilityUseCaseInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Jobs\CalculateKitApplicabilityJob;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Messaging\Handlers\CalculationRequestedHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
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

        Queue::assertPushed(CalculateKitApplicabilityJob::class, function (CalculateKitApplicabilityJob $job): bool {
            return $this->jobProperty($job, 'userId') === 42
                && $this->jobProperty($job, 'operationId') === 'operation-123'
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

    private function jobProperty(CalculateKitApplicabilityJob $job, string $property): mixed
    {
        $reflection = new ReflectionClass($job);

        return $reflection->getProperty($property)->getValue($job);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
