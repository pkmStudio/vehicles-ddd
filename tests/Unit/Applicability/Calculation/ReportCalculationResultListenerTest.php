<?php

declare(strict_types=1);

namespace Tests\Unit\Applicability\Calculation;

use App\Modules\Applicability\Features\Calculation\Application\Listeners\ReportCalculationResultListener;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting\CalculationFailureReporterInterface;
use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use App\Modules\Applicability\Shared\Domain\Events\KitApplicability\KitApplicabilityRecalculated;
use Mockery;
use Tests\TestCase;

final class ReportCalculationResultListenerTest extends TestCase
{
    public function test_listener_reports_recalculation_result(): void
    {
        $result = new KitApplicabilityCalculationResultDTO(
            runId: 'run-1',
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

        (new ReportCalculationResultListener($reporter))->handle(
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
