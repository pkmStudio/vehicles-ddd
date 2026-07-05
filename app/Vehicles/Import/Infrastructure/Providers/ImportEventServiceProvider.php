<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Providers;

use App\Vehicles\Import\Application\Listeners\EngineModificationReadinessSubscriber;
use App\Vehicles\Import\Application\Listeners\ReportImportResultListener;
use App\Vehicles\Import\Application\Listeners\StartEngineImportListener;
use App\Vehicles\Import\Application\Listeners\StartEngineModificationImportListener;
use App\Vehicles\Import\Application\Listeners\StartModificationCommandImportListener;
use App\Vehicles\Import\Application\Listeners\StartVehicleImportListener;
use App\Vehicles\Import\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Vehicles\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Vehicles\Import\Domain\Events\EnginesAndModificationsReady;
use App\Vehicles\Import\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Import\Domain\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Import\Domain\Events\Vehicle\VehicleImportCompleted;
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
        Event::listen(ManufacturerCommandImported::class, StartVehicleImportListener::class);
        Event::listen(ManufacturerCommandImported::class, StartEngineImportListener::class);

        Event::listen(VehicleCommandImported::class, StartModificationCommandImportListener::class);

        /**
         * Подписчик: слушает EngineCommandImported и ModificationCommandImported,
         * при готовности обоих — диспатчит EnginesAndModificationsReady.
         */
        Event::subscribe(EngineModificationReadinessSubscriber::class);

        /**
         * Двигатели и модификации готовы — импорт связей engine_modification.
         */
        Event::listen(EnginesAndModificationsReady::class, StartEngineModificationImportListener::class);

        /**
         * Выгрузка ошибок после завершения импортов.
         */
        Event::listen(VehicleImportCompleted::class, ReportImportResultListener::class);
        Event::listen(EngineImportCompleted::class, ReportImportResultListener::class);
        Event::listen(EngineCrossImportCompleted::class, ReportImportResultListener::class);
    }
}
