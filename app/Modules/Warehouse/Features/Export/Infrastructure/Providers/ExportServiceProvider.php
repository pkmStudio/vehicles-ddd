<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Providers;

use App\Modules\Warehouse\Features\Export\Application\Factories\ExportFileFactory;
use App\Modules\Warehouse\Features\Export\Application\Services\KitExportService;
use App\Modules\Warehouse\Features\Export\Application\Services\NomenclatureExportService;
use App\Modules\Warehouse\Features\Export\Application\Services\PackDimensionExportService;
use App\Modules\Warehouse\Features\Export\Application\Services\Rows\KitExportRow;
use App\Modules\Warehouse\Features\Export\Application\Services\Rows\NomenclatureExportRow;
use App\Modules\Warehouse\Features\Export\Application\Services\TypeTemplateResolver;
use App\Modules\Warehouse\Features\Export\Application\UseCases\External\StartExportUseCase;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients\WiperAdapterAuditClientInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\NomenclatureByTypeExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\PackDimensionExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\PackDimensionRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\KitExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\NomenclatureExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\PackDimensionExportServiceInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\Rows\KitExportRowInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\Rows\NomenclatureExportRowInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Warehouse\Features\Export\Infrastructure\Clients\TemplatesClient;
use App\Modules\Warehouse\Features\Export\Infrastructure\Clients\WiperAdapterAuditClient;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Kit\KitExport;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Nomenclature\NomenclatureByTypeExport;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\PackDimension\PackDimensionExport;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\WiperAdapterAudit\WiperAdapterAuditExport;
use App\Modules\Warehouse\Features\Export\Infrastructure\Notifications\RabbitMqExportNotificationService;
use App\Modules\Warehouse\Features\Export\Infrastructure\Repositories\KitRepository;
use App\Modules\Warehouse\Features\Export\Infrastructure\Repositories\NomenclatureRepository;
use App\Modules\Warehouse\Features\Export\Infrastructure\Repositories\PackDimensionRepository;
use App\Modules\Warehouse\Features\Export\Infrastructure\Repositories\TypeRepository;
use App\Modules\Warehouse\Features\Export\Infrastructure\Services\External\CleanupStaleExportFilesService;
use App\Modules\Warehouse\Features\Export\Infrastructure\Services\External\ExportRunCacheService;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги вертикального среза Warehouse Export.
 */
final class ExportServiceProvider extends ServiceProvider
{
    private const array EXPORT_BINDINGS = [
        NomenclatureByTypeExportInterface::class => NomenclatureByTypeExport::class,
        PackDimensionExportInterface::class => PackDimensionExport::class,
        KitExportInterface::class => KitExport::class,
        WiperAdapterAuditExportInterface::class => WiperAdapterAuditExport::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        TypeRepositoryInterface::class => TypeRepository::class,
        NomenclatureRepositoryInterface::class => NomenclatureRepository::class,
        PackDimensionRepositoryInterface::class => PackDimensionRepository::class,
        KitRepositoryInterface::class => KitRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        NomenclatureExportRowInterface::class => NomenclatureExportRow::class,
        KitExportRowInterface::class => KitExportRow::class,
        TypeTemplateResolverInterface::class => TypeTemplateResolver::class,
        NomenclatureExportServiceInterface::class => NomenclatureExportService::class,
        PackDimensionExportServiceInterface::class => PackDimensionExportService::class,
        KitExportServiceInterface::class => KitExportService::class,
        ExportRunCacheServiceInterface::class => ExportRunCacheService::class,
        CleanupStaleExportFilesServiceInterface::class => CleanupStaleExportFilesService::class,
    ];

    private const array FACTORY_BINDINGS = [
        ExportFileFactoryInterface::class => ExportFileFactory::class,
    ];

    private const array USE_CASE_BINDINGS = [
        StartExportUseCaseInterface::class => StartExportUseCase::class,
    ];

    private const array CLIENT_BINDINGS = [
        TemplatesClientInterface::class => TemplatesClient::class,
        WiperAdapterAuditClientInterface::class => WiperAdapterAuditClient::class,
    ];

    /**
     * Биндит порты Export-фичи на инфраструктурные и прикладные реализации.
     *
     * Шаги:
     * 1) Зарегистрировать исходящий notifier брокера.
     * 2) Зарегистрировать Excel-адаптеры, repositories, services, factory и use case.
     */
    public function register(): void
    {
        $this->app->bind(
            abstract: ExportNotificationServiceInterface::class,
            concrete: RabbitMqExportNotificationService::class,
        );

        foreach (self::EXPORT_BINDINGS as $interface => $implementation) {
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

        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind(
                abstract: $interface,
                concrete: $implementation,
            );
        }
    }
}
