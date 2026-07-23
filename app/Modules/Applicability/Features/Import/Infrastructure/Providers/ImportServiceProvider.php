<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Providers;

use App\Modules\Applicability\Features\Import\Application\Factories\ImportFileFactory;
use App\Modules\Applicability\Features\Import\Application\UseCases\External\StartExternalFileImportUseCase;
use App\Modules\Applicability\Features\Import\Application\Services\ImportKitApplicabilityRowService;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\VehiclesModificationClientInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\KitApplicabilityImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\ImportKitApplicabilityRowServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Applicability\Features\Import\Infrastructure\Clients\VehiclesModificationClient;
use App\Modules\Applicability\Features\Import\Infrastructure\Clients\WarehouseKitClient;
use App\Modules\Applicability\Features\Import\Infrastructure\Commands\KitApplicabilityCommand;
use App\Modules\Applicability\Features\Import\Infrastructure\Imports\KitApplicabilityImport;
use App\Modules\Applicability\Features\Import\Infrastructure\Notifications\RabbitMqImportNotificationService;
use App\Modules\Applicability\Features\Import\Infrastructure\Reporting\CacheImportFailureStore;
use App\Modules\Applicability\Features\Import\Infrastructure\Reporting\FailuresExport;
use App\Modules\Applicability\Features\Import\Infrastructure\Reporting\ImportFailureReporter;
use App\Modules\Applicability\Features\Import\Infrastructure\Services\External\ExternalImportCacheService;
use App\Modules\Applicability\Features\Import\Infrastructure\Storage\LaravelExternalImportFileStorage;
use Illuminate\Support\ServiceProvider;

final class ImportServiceProvider extends ServiceProvider
{
    private const array BINDINGS = [
        KitApplicabilityImportInterface::class => KitApplicabilityImport::class,
        WarehouseKitClientInterface::class => WarehouseKitClient::class,
        VehiclesModificationClientInterface::class => VehiclesModificationClient::class,
        KitApplicabilityCommandInterface::class => KitApplicabilityCommand::class,
        ImportKitApplicabilityRowServiceInterface::class => ImportKitApplicabilityRowService::class,
        ImportFileFactoryInterface::class => ImportFileFactory::class,
        StartExternalFileImportUseCaseInterface::class => StartExternalFileImportUseCase::class,
        ExternalImportCacheServiceInterface::class => ExternalImportCacheService::class,
        ExternalImportFileStorageInterface::class => LaravelExternalImportFileStorage::class,
        ImportNotificationServiceInterface::class => RabbitMqImportNotificationService::class,
        ImportFailureReporterInterface::class => ImportFailureReporter::class,
        ImportFailureStoreInterface::class => CacheImportFailureStore::class,
        FailuresExportInterface::class => FailuresExport::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
