<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Providers;

use App\Modules\Applicability\Features\Export\Application\Services\ModificationKitApplicabilityExportService;
use App\Modules\Applicability\Features\Export\Application\Services\VehicleKitApplicabilityExportService;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\ModificationKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\ModificationKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityReferenceServiceInterface;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\ModificationKitApplicabilityExport;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\VehicleKitApplicabilityExport;
use App\Modules\Applicability\Features\Export\Infrastructure\Factories\ExportFileFactory;
use App\Modules\Applicability\Features\Export\Infrastructure\Notifications\RabbitMqExportNotificationService;
use App\Modules\Applicability\Features\Export\Infrastructure\Repositories\KitApplicabilityExportRepository;
use App\Modules\Applicability\Features\Export\Infrastructure\Services\External\ExportRunCacheService;
use App\Modules\Applicability\Features\Export\Infrastructure\Services\VehicleKitApplicabilityReferenceService;
use Illuminate\Support\ServiceProvider;

final class ExportServiceProvider extends ServiceProvider
{
    private const array EXPORT_BINDINGS = [
        ModificationKitApplicabilityExportInterface::class => ModificationKitApplicabilityExport::class,
        VehicleKitApplicabilityExportInterface::class => VehicleKitApplicabilityExport::class,
    ];

    private const array REPOSITORY_BINDINGS = [
        KitApplicabilityExportRepositoryInterface::class => KitApplicabilityExportRepository::class,
    ];

    private const array SERVICE_BINDINGS = [
        ModificationKitApplicabilityExportServiceInterface::class => ModificationKitApplicabilityExportService::class,
        VehicleKitApplicabilityExportServiceInterface::class => VehicleKitApplicabilityExportService::class,
        VehicleKitApplicabilityReferenceServiceInterface::class => VehicleKitApplicabilityReferenceService::class,
        ExportRunCacheServiceInterface::class => ExportRunCacheService::class,
    ];

    private const array FACTORY_BINDINGS = [
        ExportFileFactoryInterface::class => ExportFileFactory::class,
    ];

    private const array NOTIFICATION_BINDINGS = [
        ExportNotificationServiceInterface::class => RabbitMqExportNotificationService::class,
    ];

    /**
     * Регистрирует bindings фичи Applicability Export.
     *
     * Шаги:
     * 1. Получает сгруппированные массивы bindings по типам ports.
     * 2. Регистрирует каждый interface-to-implementation mapping в Laravel container.
     */
    public function register(): void
    {
        foreach ($this->bindings() as $bindings) {
            foreach ($bindings as $interface => $implementation) {
                $this->app->bind($interface, $implementation);
            }
        }
    }

    /**
     * Возвращает сгруппированные DI bindings фичи Export.
     *
     * Шаги:
     * 1. Собирает bindings exports, repositories, services, factories и notifications.
     * 2. Возвращает группы единым списком для `register()`.
     */
    private function bindings(): array
    {
        return [
            self::EXPORT_BINDINGS,
            self::REPOSITORY_BINDINGS,
            self::SERVICE_BINDINGS,
            self::FACTORY_BINDINGS,
            self::NOTIFICATION_BINDINGS,
        ];
    }
}
