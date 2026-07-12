<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Providers;

use App\Warehouse\Import\Application\Listeners\CleanupExternalImportFileListener;
use App\Warehouse\Import\Application\Listeners\ReportImportResultListener;
use App\Warehouse\Import\Domain\Events\KitImportCompleted;
use App\Warehouse\Import\Domain\Events\NomenclatureImportCompleted;
use App\Warehouse\Import\Domain\Events\PackDimensionImportCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует реакцию на завершение Warehouse-импорта. Nomenclature/PackDimension/Kit —
 * родственные события с одной и той же реакцией у каждого листенера, поэтому не `Subscriber`, а
 * повторный `Event::listen` на каждое (см. ARCHITECTURE.md §2).
 */
final class ImportEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(NomenclatureImportCompleted::class, [ReportImportResultListener::class, 'handle']);
        Event::listen(PackDimensionImportCompleted::class, [ReportImportResultListener::class, 'handle']);
        Event::listen(KitImportCompleted::class, [ReportImportResultListener::class, 'handle']);

        Event::listen(NomenclatureImportCompleted::class, [CleanupExternalImportFileListener::class, 'handle']);
        Event::listen(PackDimensionImportCompleted::class, [CleanupExternalImportFileListener::class, 'handle']);
        Event::listen(KitImportCompleted::class, [CleanupExternalImportFileListener::class, 'handle']);
    }
}
