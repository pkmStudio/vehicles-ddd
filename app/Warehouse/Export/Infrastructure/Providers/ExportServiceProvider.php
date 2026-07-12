<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Infrastructure\Providers;

use App\Warehouse\Export\Application\Factories\ExportFileFactory;
use App\Warehouse\Export\Application\Services\External\CleanupStaleExportFilesService;
use App\Warehouse\Export\Application\Services\External\ExportRunCacheService;
use App\Warehouse\Export\Application\Services\KitExportService;
use App\Warehouse\Export\Application\Services\NomenclatureExportService;
use App\Warehouse\Export\Application\Services\Rows\KitExportRow;
use App\Warehouse\Export\Application\Services\Rows\NomenclatureExportRow;
use App\Warehouse\Export\Application\Services\TypeTemplateResolver;
use App\Warehouse\Export\Application\UseCases\External\StartExportUseCase;
use App\Warehouse\Export\Domain\Contracts\Exports\KitExportInterface;
use App\Warehouse\Export\Domain\Contracts\Exports\NomenclatureByTypeExportInterface;
use App\Warehouse\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Warehouse\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Warehouse\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Warehouse\Export\Domain\Contracts\Repositories\KitRepositoryInterface;
use App\Warehouse\Export\Domain\Contracts\Repositories\NomenclatureRepositoryInterface;
use App\Warehouse\Export\Domain\Contracts\Repositories\TypeRepositoryInterface;
use App\Warehouse\Export\Domain\Contracts\Services\External\CleanupStaleExportFilesServiceInterface;
use App\Warehouse\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Warehouse\Export\Domain\Contracts\Services\KitExportServiceInterface;
use App\Warehouse\Export\Domain\Contracts\Services\NomenclatureExportServiceInterface;
use App\Warehouse\Export\Domain\Contracts\Services\Rows\KitExportRowInterface;
use App\Warehouse\Export\Domain\Contracts\Services\Rows\NomenclatureExportRowInterface;
use App\Warehouse\Export\Domain\Contracts\Services\TypeTemplateResolverInterface;
use App\Warehouse\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Warehouse\Export\Infrastructure\Exports\Kit\KitExport;
use App\Warehouse\Export\Infrastructure\Exports\Nomenclature\NomenclatureByTypeExport;
use App\Warehouse\Export\Infrastructure\Exports\WiperAdapterAudit\WiperAdapterAuditExport;
use App\Warehouse\Export\Infrastructure\Notifications\RabbitMqExportNotificationService;
use App\Warehouse\Export\Infrastructure\Repositories\KitRepository;
use App\Warehouse\Export\Infrastructure\Repositories\NomenclatureRepository;
use App\Warehouse\Export\Infrastructure\Repositories\TypeRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги вертикального среза Warehouse Export.
 */
final class ExportServiceProvider extends ServiceProvider
{
    private const array EXPORT_BINDINGS = [
        NomenclatureByTypeExportInterface::class => NomenclatureByTypeExport::class,
        KitExportInterface::class => KitExport::class,
        WiperAdapterAuditExportInterface::class => WiperAdapterAuditExport::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        TypeRepositoryInterface::class => TypeRepository::class,
        NomenclatureRepositoryInterface::class => NomenclatureRepository::class,
        KitRepositoryInterface::class => KitRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        NomenclatureExportRowInterface::class => NomenclatureExportRow::class,
        KitExportRowInterface::class => KitExportRow::class,
        TypeTemplateResolverInterface::class => TypeTemplateResolver::class,
        NomenclatureExportServiceInterface::class => NomenclatureExportService::class,
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
    }
}
