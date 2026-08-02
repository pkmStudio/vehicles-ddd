<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Providers;

use App\Modules\Warehouse\Features\Import\Application\Factories\ImportFileFactory;
use App\Modules\Warehouse\Features\Import\Application\Services\Kit\ImportKitFromRowService;
use App\Modules\Warehouse\Features\Import\Application\Services\Nomenclature\ImportNomenclatureFromRowService;
use App\Modules\Warehouse\Features\Import\Application\Services\PackDimension\ImportPackDimensionFromRowService;
use App\Modules\Warehouse\Features\Import\Application\Services\TypeTemplateResolver;
use App\Modules\Warehouse\Features\Import\Application\UseCases\External\PublishLocalImportRequestUseCase;
use App\Modules\Warehouse\Features\Import\Application\UseCases\External\StartExternalFileImportUseCase;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Publishers\LocalImportRequestPublisherInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Kit\ImportKitFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\Nomenclature\ImportNomenclatureFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\PackDimension\ImportPackDimensionFromRowServiceInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Storage\LocalImportFileStorageInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\UseCases\External\PublishLocalImportRequestUseCaseInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Warehouse\Features\Import\Infrastructure\Clients\KitPropertiesClient;
use App\Modules\Warehouse\Features\Import\Infrastructure\Clients\TemplatesClient;
use App\Modules\Warehouse\Features\Import\Infrastructure\Commands\KitCommand;
use App\Modules\Warehouse\Features\Import\Infrastructure\Commands\NomenclatureCommand;
use App\Modules\Warehouse\Features\Import\Infrastructure\Commands\PackDimensionCommand;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Kit\KitImport;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\Nomenclature\NomenclatureImport;
use App\Modules\Warehouse\Features\Import\Infrastructure\Imports\PackDimension\PackDimensionImport;
use App\Modules\Warehouse\Features\Import\Infrastructure\Notifications\RabbitMqImportNotificationService;
use App\Modules\Warehouse\Features\Import\Infrastructure\Publishers\RabbitMqLocalImportRequestPublisher;
use App\Modules\Warehouse\Features\Import\Infrastructure\Reporting\CacheImportFailureStore;
use App\Modules\Warehouse\Features\Import\Infrastructure\Reporting\FailuresExport;
use App\Modules\Warehouse\Features\Import\Infrastructure\Reporting\ImportFailureReporter;
use App\Modules\Warehouse\Features\Import\Infrastructure\Repositories\BrandRepository;
use App\Modules\Warehouse\Features\Import\Infrastructure\Repositories\KitRepository;
use App\Modules\Warehouse\Features\Import\Infrastructure\Repositories\NomenclatureRepository;
use App\Modules\Warehouse\Features\Import\Infrastructure\Repositories\PackDimensionRepository;
use App\Modules\Warehouse\Features\Import\Infrastructure\Repositories\TypeRepository;
use App\Modules\Warehouse\Features\Import\Infrastructure\Services\External\ExternalImportCacheService;
use App\Modules\Warehouse\Features\Import\Infrastructure\Storage\LaravelExternalImportFileStorage;
use App\Modules\Warehouse\Features\Import\Infrastructure\Storage\LaravelLocalImportFileStorage;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги вертикального среза Warehouse Import.
 */
final class ImportServiceProvider extends ServiceProvider
{
    private const array IMPORT_BINDINGS = [
        NomenclatureImportInterface::class => NomenclatureImport::class,
        PackDimensionImportInterface::class => PackDimensionImport::class,
        KitImportInterface::class => KitImport::class,
    ];

    private const array COMMAND_BINDINGS = [
        NomenclatureCommandInterface::class => NomenclatureCommand::class,
        PackDimensionCommandInterface::class => PackDimensionCommand::class,
        KitCommandInterface::class => KitCommand::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        TypeRepositoryInterface::class => TypeRepository::class,
        BrandRepositoryInterface::class => BrandRepository::class,
        NomenclatureRepositoryInterface::class => NomenclatureRepository::class,
        PackDimensionRepositoryInterface::class => PackDimensionRepository::class,
        KitRepositoryInterface::class => KitRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        TypeTemplateResolverInterface::class => TypeTemplateResolver::class,
        ImportNomenclatureFromRowServiceInterface::class => ImportNomenclatureFromRowService::class,
        ImportPackDimensionFromRowServiceInterface::class => ImportPackDimensionFromRowService::class,
        ImportKitFromRowServiceInterface::class => ImportKitFromRowService::class,
        ExternalImportCacheServiceInterface::class => ExternalImportCacheService::class,
        ExternalImportFileStorageInterface::class => LaravelExternalImportFileStorage::class,
        LocalImportFileStorageInterface::class => LaravelLocalImportFileStorage::class,
    ];

    private const array FACTORY_BINDINGS = [
        ImportFileFactoryInterface::class => ImportFileFactory::class,
    ];

    private const array USE_CASE_BINDINGS = [
        StartExternalFileImportUseCaseInterface::class => StartExternalFileImportUseCase::class,
        PublishLocalImportRequestUseCaseInterface::class => PublishLocalImportRequestUseCase::class,
    ];

    private const array REPORTING_BINDINGS = [
        ImportFailureReporterInterface::class => ImportFailureReporter::class,
        ImportFailureStoreInterface::class => CacheImportFailureStore::class,
        FailuresExportInterface::class => FailuresExport::class,
    ];

    private const array CLIENT_BINDINGS = [
        KitPropertiesClientInterface::class => KitPropertiesClient::class,
        TemplatesClientInterface::class => TemplatesClient::class,
    ];

    private const array PUBLISHER_BINDINGS = [
        LocalImportRequestPublisherInterface::class => RabbitMqLocalImportRequestPublisher::class,
    ];

    /**
     * Биндит порты Import-фичи на инфраструктурные и прикладные реализации.
     *
     * Шаги:
     * 1) Зарегистрировать исходящий notifier брокера.
     * 2) Зарегистрировать Excel-адаптеры, commands, repositories, services, factory, use case и reporting.
     */
    public function register(): void
    {
        $this->app->bind(
            abstract: ImportNotificationServiceInterface::class,
            concrete: RabbitMqImportNotificationService::class,
        );

        foreach (self::IMPORT_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::COMMAND_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::REPOSITORY_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::FACTORY_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::USE_CASE_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::REPORTING_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }

        foreach (self::PUBLISHER_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }
    }
}
