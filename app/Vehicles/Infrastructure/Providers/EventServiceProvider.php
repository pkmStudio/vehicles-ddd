<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Vehicles\Domain\Events\Engine\EngineImportCompleted;
use App\Vehicles\Domain\Events\EnginesAndModificationsReady;
use App\Vehicles\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Domain\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Domain\Events\Vehicle\VehicleImportCompleted;
use App\Vehicles\Application\Listeners\ReportImportResultListener;
use App\Vehicles\Application\Listeners\StartEngineImportListener;
use App\Vehicles\Application\Listeners\StartEngineModificationImportListener;
use App\Vehicles\Application\Listeners\StartModificationCommandImportListener;
use App\Vehicles\Application\Listeners\StartVehicleImportListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
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
        Event::subscribe(EnginesAndModificationsReady::class);

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
