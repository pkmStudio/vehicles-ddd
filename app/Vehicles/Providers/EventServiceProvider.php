<?php

declare(strict_types=1);

namespace App\Vehicles\Providers;

use App\Vehicles\Events\Engine\EngineCrossImportCompleted;
use App\Vehicles\Events\Engine\EngineImportCompleted;
use App\Vehicles\Events\EnginesAndModificationsReady;
use App\Vehicles\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Events\Vehicle\VehicleImportCompleted;
use App\Vehicles\Listeners\ExportImportErrors;
use App\Vehicles\Listeners\StartEngineImport;
use App\Vehicles\Listeners\StartEngineModificationImport;
use App\Vehicles\Listeners\StartModificationCommandImport;
use App\Vehicles\Listeners\StartVehicleImport;
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
        Event::listen(ManufacturerCommandImported::class, StartVehicleImport::class);
        Event::listen(ManufacturerCommandImported::class, StartEngineImport::class);

        Event::listen(VehicleCommandImported::class, StartModificationCommandImport::class);

        /**
         * Подписчик: слушает EngineCommandImported и ModificationCommandImported,
         * при готовности обоих — диспатчит EnginesAndModificationsReady.
         */
        Event::subscribe(EnginesAndModificationsReady::class);

        /**
         * Двигатели и модификации готовы — импорт связей engine_modification.
         */
        Event::listen(EnginesAndModificationsReady::class, StartEngineModificationImport::class);

        /**
         * Выгрузка ошибок после завершения импортов.
         */
        Event::listen(VehicleImportCompleted::class, ExportImportErrors::class);
        Event::listen(EngineImportCompleted::class, ExportImportErrors::class);
        Event::listen(EngineCrossImportCompleted::class, ExportImportErrors::class);
    }
}
