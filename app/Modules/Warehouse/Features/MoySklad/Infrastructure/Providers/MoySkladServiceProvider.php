<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\MoySklad\Infrastructure\Providers;

use App\Modules\Warehouse\Features\MoySklad\Application\Listeners\Nomenclature\DeleteNomenclatureInMoySkladListener;
use App\Modules\Warehouse\Features\MoySklad\Application\Listeners\Nomenclature\SyncCreatedNomenclatureListener;
use App\Modules\Warehouse\Features\MoySklad\Application\Listeners\Nomenclature\SyncUpdatedNomenclatureListener;
use App\Modules\Warehouse\Features\MoySklad\Application\Services\NomenclatureSyncService;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Commands\NomenclatureIntegrationCommandInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\MoySklad\Domain\Contracts\Services\NomenclatureSyncServiceInterface;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Clients\MoySkladProductClient;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Commands\NomenclatureIntegrationCommand;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Dispatchers\QueueNomenclatureSyncDispatcher;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Repositories\NomenclatureIntegrationRepository;
use App\Modules\Warehouse\Features\MoySklad\Infrastructure\Repositories\NomenclatureRepository;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;
use App\Modules\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI и event listeners фичи Warehouse/MoySklad.
 */
final class MoySkladServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует локальные порты фичи.
     */
    public function register(): void
    {
        $this->app->bind(
            abstract: MoySkladProductClientInterface::class,
            concrete: MoySkladProductClient::class,
        );

        $this->app->bind(
            abstract: NomenclatureRepositoryInterface::class,
            concrete: NomenclatureRepository::class,
        );

        $this->app->bind(
            abstract: NomenclatureIntegrationRepositoryInterface::class,
            concrete: NomenclatureIntegrationRepository::class,
        );

        $this->app->bind(
            abstract: NomenclatureIntegrationCommandInterface::class,
            concrete: NomenclatureIntegrationCommand::class,
        );

        $this->app->bind(
            abstract: NomenclatureSyncDispatcherInterface::class,
            concrete: QueueNomenclatureSyncDispatcher::class,
        );

        $this->app->bind(
            abstract: NomenclatureSyncServiceInterface::class,
            concrete: NomenclatureSyncService::class,
        );
    }

    /**
     * Подписывает MoySklad на публичные события Warehouse.
     */
    public function boot(): void
    {
        Event::listen(
            events: NomenclatureCreated::class,
            listener: [SyncCreatedNomenclatureListener::class, 'handle'],
        );

        Event::listen(
            events: NomenclatureUpdated::class,
            listener: [SyncUpdatedNomenclatureListener::class, 'handle'],
        );

        Event::listen(
            events: NomenclatureDeleted::class,
            listener: [DeleteNomenclatureInMoySkladListener::class, 'handle'],
        );
    }
}
