<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Providers;

use App\Modules\Warehouse\Features\Import\Application\Listeners\CleanupExternalImportFileListener;
use App\Modules\Warehouse\Features\Import\Application\Listeners\ReportImportResultListener;
use App\Modules\Warehouse\Features\Import\Domain\Events\KitImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Events\NomenclatureImportCompleted;
use App\Modules\Warehouse\Features\Import\Domain\Events\PackDimensionImportCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует реакцию на завершение Warehouse-импорта. Nomenclature/PackDimension/Kit —
 * родственные события с одной и той же реакцией у каждого листенера, поэтому не `Subscriber`, а
 * повторный `Event::listen` на каждое (см. ARCHITECTURE.md §2).
 */
final class ImportEventServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует одинаковые listeners на все родственные события завершения импорта.
     *
     * Шаги:
     * 1) Подписать ReportImportResultListener на completion-события каждого типа импорта.
     * 2) Подписать CleanupExternalImportFileListener на те же completion-события.
     * 3) Оставить выбор конкретного типа импорта внутри listener'а.
     */
    public function boot(): void
    {
        Event::listen(
            events: NomenclatureImportCompleted::class,
            listener: [ReportImportResultListener::class, 'handle'],
        );
        Event::listen(
            events: PackDimensionImportCompleted::class,
            listener: [ReportImportResultListener::class, 'handle'],
        );
        Event::listen(
            events: KitImportCompleted::class,
            listener: [ReportImportResultListener::class, 'handle'],
        );

        Event::listen(
            events: NomenclatureImportCompleted::class,
            listener: [CleanupExternalImportFileListener::class, 'handle'],
        );
        Event::listen(
            events: PackDimensionImportCompleted::class,
            listener: [CleanupExternalImportFileListener::class, 'handle'],
        );
        Event::listen(
            events: KitImportCompleted::class,
            listener: [CleanupExternalImportFileListener::class, 'handle'],
        );
    }
}
