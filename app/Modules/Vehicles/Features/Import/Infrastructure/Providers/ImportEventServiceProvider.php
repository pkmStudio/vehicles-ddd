<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Providers;

use App\Modules\Vehicles\Features\Import\Application\Listeners\EngineModificationReadinessSubscriber;
use App\Modules\Vehicles\Features\Import\Application\Listeners\CleanupExternalImportFileListener;
use App\Modules\Vehicles\Features\Import\Application\Listeners\ReportImportResultListener;
use App\Modules\Vehicles\Features\Import\Application\Listeners\Command\StartEngineCommandImportListener;
use App\Modules\Vehicles\Features\Import\Application\Listeners\Command\StartEngineModificationCommandImportListener;
use App\Modules\Vehicles\Features\Import\Application\Listeners\Command\StartModificationCommandImportListener;
use App\Modules\Vehicles\Features\Import\Application\Listeners\Command\StartVehicleCommandImportListener;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\EnginesAndModificationsReady;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleCommandImported;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleImportCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ImportEventServiceProvider extends ServiceProvider
{
    /**
     * Связи событий и слушателей домена Vehicles.
     */
    public function boot(): void
    {
        /**
         * Цепочка импортных команд.
         */
        Event::listen(ManufacturerCommandImported::class, StartVehicleCommandImportListener::class);
        Event::listen(ManufacturerCommandImported::class, StartEngineCommandImportListener::class);

        Event::listen(VehicleCommandImported::class, StartModificationCommandImportListener::class);

        /**
         * Подписчик: слушает EngineCommandImported и ModificationCommandImported,
         * при готовности обоих — диспатчит EnginesAndModificationsReady.
         */
        Event::subscribe(EngineModificationReadinessSubscriber::class);

        /**
         * Двигатели и модификации готовы — импорт связей engine_modification.
         */
        Event::listen(EnginesAndModificationsReady::class, StartEngineModificationCommandImportListener::class);

        /**
         * Выгрузка ошибок после завершения импортов.
         */
        Event::listen(VehicleImportCompleted::class, ReportImportResultListener::class);
        Event::listen(EngineImportCompleted::class, ReportImportResultListener::class);
        Event::listen(EngineCrossImportCompleted::class, ReportImportResultListener::class);

        Event::listen(VehicleImportCompleted::class, CleanupExternalImportFileListener::class);
        Event::listen(EngineImportCompleted::class, CleanupExternalImportFileListener::class);
        Event::listen(EngineCrossImportCompleted::class, CleanupExternalImportFileListener::class);
    }
}
