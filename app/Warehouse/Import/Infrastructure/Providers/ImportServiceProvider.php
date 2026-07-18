<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Providers;

use App\Warehouse\Import\Application\Factories\ImportFileFactory;
use App\Warehouse\Import\Application\Services\External\ExternalImportCacheService;
use App\Warehouse\Import\Application\Services\Kit\UpsertKitFromRowService;
use App\Warehouse\Import\Application\Services\Nomenclature\UpsertNomenclatureFromRowService;
use App\Warehouse\Import\Application\Services\PackDimension\UpsertPackDimensionFromRowService;
use App\Warehouse\Import\Application\Services\TypeTemplateResolver;
use App\Warehouse\Import\Application\UseCases\External\StartExternalFileImportUseCase;
use App\Warehouse\Import\Domain\Contracts\Commands\KitCommandInterface;
use App\Warehouse\Import\Domain\Contracts\Commands\NomenclatureCommandInterface;
use App\Warehouse\Import\Domain\Contracts\Commands\PackDimensionCommandInterface;
use App\Warehouse\Import\Domain\Contracts\Clients\KitPropertiesClientInterface;
use App\Warehouse\Import\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Warehouse\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Warehouse\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Warehouse\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Warehouse\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Warehouse\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Warehouse\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Warehouse\Import\Domain\Contracts\Reporting\ImportFailureReporterInterface;
use App\Warehouse\Import\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Warehouse\Import\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Import\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Warehouse\Import\Domain\Contracts\Services\Kit\UpsertKitFromRowServiceInterface;
use App\Warehouse\Import\Domain\Contracts\Services\Nomenclature\UpsertNomenclatureFromRowServiceInterface;
use App\Warehouse\Import\Domain\Contracts\Services\PackDimension\UpsertPackDimensionFromRowServiceInterface;
use App\Warehouse\Import\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Warehouse\Import\Infrastructure\Commands\KitCommand;
use App\Warehouse\Import\Infrastructure\Commands\NomenclatureCommand;
use App\Warehouse\Import\Infrastructure\Commands\PackDimensionCommand;
use App\Warehouse\Import\Infrastructure\Clients\KitPropertiesClient;
use App\Warehouse\Import\Infrastructure\Clients\TemplatesClient;
use App\Warehouse\Import\Infrastructure\Imports\Kit\KitImport;
use App\Warehouse\Import\Infrastructure\Imports\Nomenclature\NomenclatureImport;
use App\Warehouse\Import\Infrastructure\Imports\PackDimension\PackDimensionImport;
use App\Warehouse\Import\Infrastructure\Notifications\RabbitMqImportNotificationService;
use App\Warehouse\Import\Infrastructure\Reporting\FailuresExport;
use App\Warehouse\Import\Infrastructure\Reporting\ImportFailureReporter;
use App\Warehouse\Import\Infrastructure\Repositories\BrandRepository;
use App\Warehouse\Import\Infrastructure\Repositories\NomenclatureRepository;
use App\Warehouse\Import\Infrastructure\Repositories\TypeRepository;
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
    ];

    private const array SERVICE_BINDINGS = [
        TypeTemplateResolverInterface::class => TypeTemplateResolver::class,
        UpsertNomenclatureFromRowServiceInterface::class => UpsertNomenclatureFromRowService::class,
        UpsertPackDimensionFromRowServiceInterface::class => UpsertPackDimensionFromRowService::class,
        UpsertKitFromRowServiceInterface::class => UpsertKitFromRowService::class,
        ExternalImportCacheServiceInterface::class => ExternalImportCacheService::class,
    ];

    private const array FACTORY_BINDINGS = [
        ImportFileFactoryInterface::class => ImportFileFactory::class,
    ];

    private const array USE_CASE_BINDINGS = [
        StartExternalFileImportUseCaseInterface::class => StartExternalFileImportUseCase::class,
    ];

    private const array REPORTING_BINDINGS = [
        ImportFailureReporterInterface::class => ImportFailureReporter::class,
        FailuresExportInterface::class => FailuresExport::class,
    ];

    private const array CLIENT_BINDINGS = [
        KitPropertiesClientInterface::class => KitPropertiesClient::class,
        TemplatesClientInterface::class => TemplatesClient::class,
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
    }
}
