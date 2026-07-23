<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Providers;

use App\Modules\Applicability\Features\Calculation\Application\Listeners\ReportCalculationResultListener;
use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class CalculationEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(
            events: KitApplicabilityRecalculated::class,
            listener: [ReportCalculationResultListener::class, 'handle'],
        );
    }
}
