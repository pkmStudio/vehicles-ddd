<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Providers;

use App\Modules\Applicability\Features\Export\Application\Factories\ExportFileFactory;
use App\Modules\Applicability\Features\Export\Application\Services\VehicleKitApplicabilityExportService;
use App\Modules\Applicability\Features\Export\Application\UseCases\External\StartExportUseCase;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\VehicleKitApplicabilityExport;
use App\Modules\Applicability\Features\Export\Infrastructure\Notifications\RabbitMqExportNotificationService;
use App\Modules\Applicability\Features\Export\Infrastructure\Repositories\KitApplicabilityExportRepository;
use App\Modules\Applicability\Features\Export\Infrastructure\Services\External\ExportRunCacheService;
use Illuminate\Support\ServiceProvider;

final class ExportServiceProvider extends ServiceProvider
{
    private const array BINDINGS = [
        VehicleKitApplicabilityExportInterface::class => VehicleKitApplicabilityExport::class,
        KitApplicabilityExportRepositoryInterface::class => KitApplicabilityExportRepository::class,
        VehicleKitApplicabilityExportServiceInterface::class => VehicleKitApplicabilityExportService::class,
        ExportFileFactoryInterface::class => ExportFileFactory::class,
        StartExportUseCaseInterface::class => StartExportUseCase::class,
        ExportRunCacheServiceInterface::class => ExportRunCacheService::class,
        ExportNotificationServiceInterface::class => RabbitMqExportNotificationService::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
