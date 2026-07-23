<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Providers;

use App\Modules\Applicability\Features\Calculation\Application\Services\ApplicabilityServiceFactory;
use App\Modules\Applicability\Features\Calculation\Application\Services\KitApplicabilityCalculator;
use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperAdapterExtractor;
use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperApplicabilityService;
use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperDataExtractor;
use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperLengthExtractor;
use App\Modules\Applicability\Features\Calculation\Application\Services\Wiper\WiperVehicleFinder;
use App\Modules\Applicability\Features\Calculation\Application\UseCases\CalculateKitApplicabilityUseCase;
use App\Modules\Applicability\Features\Calculation\Application\Listeners\ReportCalculationResultListener;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\TemplatesClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\VehiclesApplicabilityClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Reporting\CalculationFailureReporterInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ApplicabilityServiceFactoryInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\KitApplicabilityCalculatorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperAdapterExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperApplicabilityServiceInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperDataExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperLengthExtractorInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\Wiper\WiperVehicleFinderInterface;
use App\Modules\Applicability\Features\Calculation\Domain\Contracts\UseCases\CalculateKitApplicabilityUseCaseInterface;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Clients\TemplatesClient;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Clients\VehiclesApplicabilityClient;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Clients\WarehouseKitClient;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Commands\KitApplicabilityCommand;
use App\Modules\Applicability\Features\Calculation\Infrastructure\Reporting\CalculationFailureReporter;
use App\Modules\Applicability\Shared\Infrastructure\Logging\LaravelLoggerProxy;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class CalculationServiceProvider extends ServiceProvider
{
    private const array CLIENT_BINDINGS = [
        WarehouseKitClientInterface::class => WarehouseKitClient::class,
        VehiclesApplicabilityClientInterface::class => VehiclesApplicabilityClient::class,
        TemplatesClientInterface::class => TemplatesClient::class,
    ];

    private const array COMMAND_BINDINGS = [
        KitApplicabilityCommandInterface::class => KitApplicabilityCommand::class,
    ];

    private const array SERVICE_BINDINGS = [
        ApplicabilityServiceFactoryInterface::class => ApplicabilityServiceFactory::class,
        KitApplicabilityCalculatorInterface::class => KitApplicabilityCalculator::class,
        WiperApplicabilityServiceInterface::class => WiperApplicabilityService::class,
        WiperDataExtractorInterface::class => WiperDataExtractor::class,
        WiperLengthExtractorInterface::class => WiperLengthExtractor::class,
        WiperAdapterExtractorInterface::class => WiperAdapterExtractor::class,
        WiperVehicleFinderInterface::class => WiperVehicleFinder::class,
    ];

    private const array REPORTING_BINDINGS = [
        CalculationFailureReporterInterface::class => CalculationFailureReporter::class,
    ];

    private const array USE_CASE_BINDINGS = [
        CalculateKitApplicabilityUseCaseInterface::class => CalculateKitApplicabilityUseCase::class,
    ];

    public function register(): void
    {
        foreach (self::CLIENT_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::COMMAND_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::SERVICE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::REPORTING_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        foreach (self::USE_CASE_BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        $this->app
            ->when([
                WiperVehicleFinder::class,
                ReportCalculationResultListener::class,
            ])
            ->needs(LoggerInterface::class)
            ->give(LaravelLoggerProxy::class);
    }
}
