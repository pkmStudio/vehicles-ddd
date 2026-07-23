<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Providers;

use App\Modules\Applicability\Features\Import\Application\Listeners\CleanupExternalImportFileListener;
use App\Modules\Applicability\Features\Import\Application\Listeners\ReportImportResultListener;
use App\Modules\Applicability\Features\Import\Domain\Events\KitApplicabilityImportCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ImportEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(
            events: KitApplicabilityImportCompleted::class,
            listener: [ReportImportResultListener::class, 'handle'],
        );

        Event::listen(
            events: KitApplicabilityImportCompleted::class,
            listener: [CleanupExternalImportFileListener::class, 'handle'],
        );
    }
}
