<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Providers;

use App\Vehicles\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Vehicles\Domain\Events\Engine\EngineImportCompleted;
use App\Vehicles\Domain\Events\EnginesAndModificationsReady;
use App\Vehicles\Domain\Events\Manufacturer\ManufacturerCommandImported;
use App\Vehicles\Domain\Events\Vehicle\VehicleCommandImported;
use App\Vehicles\Domain\Events\Vehicle\VehicleImportCompleted;
use App\Vehicles\Application\Listeners\ExportImportErrors;
use App\Vehicles\Application\Listeners\StartEngineImport;
use App\Vehicles\Application\Listeners\StartEngineModificationImport;
use App\Vehicles\Application\Listeners\StartModificationCommandImport;
use App\Vehicles\Application\Listeners\StartVehicleImport;
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
