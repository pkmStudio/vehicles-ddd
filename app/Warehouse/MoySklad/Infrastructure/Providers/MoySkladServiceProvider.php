<?php

declare(strict_types=1);

namespace App\Warehouse\MoySklad\Infrastructure\Providers;

use App\Warehouse\MoySklad\Application\Listeners\Nomenclature\DeleteNomenclatureInMoySkladListener;
use App\Warehouse\MoySklad\Application\Listeners\Nomenclature\SyncCreatedNomenclatureListener;
use App\Warehouse\MoySklad\Application\Listeners\Nomenclature\SyncUpdatedNomenclatureListener;
use App\Warehouse\MoySklad\Application\Services\NomenclatureSyncService;
use App\Warehouse\MoySklad\Domain\Contracts\Clients\MoySkladProductClientInterface;
use App\Warehouse\MoySklad\Domain\Contracts\Commands\NomenclatureIntegrationCommandInterface;
use App\Warehouse\MoySklad\Domain\Contracts\Dispatchers\NomenclatureSyncDispatcherInterface;
use App\Warehouse\MoySklad\Domain\Contracts\Repositories\NomenclatureIntegrationRepositoryInterface;
use App\Warehouse\MoySklad\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\MoySklad\Domain\Contracts\Services\NomenclatureSyncServiceInterface;
use App\Warehouse\MoySklad\Infrastructure\Clients\MoySkladProductClient;
use App\Warehouse\MoySklad\Infrastructure\Commands\NomenclatureIntegrationCommand;
use App\Warehouse\MoySklad\Infrastructure\Dispatchers\QueueNomenclatureSyncDispatcher;
use App\Warehouse\MoySklad\Infrastructure\Repositories\NomenclatureIntegrationRepository;
use App\Warehouse\MoySklad\Infrastructure\Repositories\NomenclatureRepository;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureCreated;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureDeleted;
use App\Warehouse\Shared\Domain\Events\Nomenclature\NomenclatureUpdated;
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
