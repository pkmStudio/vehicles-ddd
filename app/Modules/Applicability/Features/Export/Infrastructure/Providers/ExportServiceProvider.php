<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Providers;

use App\Modules\Applicability\Features\Export\Application\Services\VehicleKitApplicabilityExportService;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\VehicleKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Repositories\KitApplicabilityExportRepositoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\VehicleKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\VehicleKitApplicabilityExport;
use App\Modules\Applicability\Features\Export\Infrastructure\Repositories\KitApplicabilityExportRepository;
use Illuminate\Support\ServiceProvider;

final class ExportServiceProvider extends ServiceProvider
{
    private const array BINDINGS = [
        VehicleKitApplicabilityExportInterface::class => VehicleKitApplicabilityExport::class,
        KitApplicabilityExportRepositoryInterface::class => KitApplicabilityExportRepository::class,
        VehicleKitApplicabilityExportServiceInterface::class => VehicleKitApplicabilityExportService::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
