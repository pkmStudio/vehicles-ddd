<?php

declare(strict_types=1);

namespace Tests\Unit\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Application\Listeners\ReportCalculationResultListener;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Notifications\CalculationNotificationServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting\CalculationFailureReporterInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\CalculationCompletionNotificationDTO;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Features\Calculation\Domain\Enums\CalculationCompletionStatusEnum;
use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use Mockery;
use Psr\Log\NullLogger;
use Tests\TestCase;

final class ReportCalculationResultListenerTest extends TestCase
{
    public function test_listener_reports_recalculation_result(): void
    {
        $result = new KitApplicabilityCalculationResultDTO(
            operationId: 'run-1',
            processedKits: 2,
            failedKits: 1,
            errors: ['Kit 10: Unknown kitType'],
        );

        $reporter = Mockery::mock(CalculationFailureReporterInterface::class);
        $reporter
            ->shouldReceive('store')
            ->once()
            ->with($result)
            ->andReturn('exports/applicability-calculation-failures-run-1.csv');

        $notifications = Mockery::mock(CalculationNotificationServiceInterface::class);
        $notifications
            ->shouldReceive('notifyCalculationCompleted')
            ->once()
            ->with(Mockery::on(static fn (CalculationCompletionNotificationDTO $payload): bool => $payload->status === CalculationCompletionStatusEnum::COMPLETED_WITH_FAILURES
                && $payload->operationId === 'run-1'
                && $payload->processedKits === 2
                && $payload->failedKits === 1
                && $payload->failuresReportPath === 'exports/applicability-calculation-failures-run-1.csv'
                && $payload->failuresReportDisk === 'exports'));

        (new ReportCalculationResultListener($reporter, $notifications, new NullLogger))->handle(
            new KitApplicabilityRecalculated('run-1', $result),
        );

        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
