<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Providers;

use App\Modules\Applicability\Features\Calculation\Application\Listeners\ReportCalculationResultListener;
use App\Modules\Applicability\Features\Calculation\Domain\Events\KitApplicabilityRecalculated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class CalculationEventServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует in-process listeners calculation-фичи.
     *
     * Шаги:
     * 1. Подписывает факт `KitApplicabilityRecalculated`.
     * 2. Передает обработку listener-у отчета и notification результата.
     */
    public function boot(): void
    {
        Event::listen(
            events: KitApplicabilityRecalculated::class,
            listener: [ReportCalculationResultListener::class, 'handle'],
        );
    }
}
