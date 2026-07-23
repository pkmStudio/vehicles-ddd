<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Providers;

use App\Modules\Applicability\Features\Import\Application\Services\ImportKitApplicabilityRowService;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\VehiclesModificationClientInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Clients\WarehouseKitClientInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Commands\KitApplicabilityCommandInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\KitApplicabilityImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\ImportKitApplicabilityRowServiceInterface;
use App\Modules\Applicability\Features\Import\Infrastructure\Clients\VehiclesModificationClient;
use App\Modules\Applicability\Features\Import\Infrastructure\Clients\WarehouseKitClient;
use App\Modules\Applicability\Features\Import\Infrastructure\Commands\KitApplicabilityCommand;
use App\Modules\Applicability\Features\Import\Infrastructure\Imports\KitApplicabilityImport;
use Illuminate\Support\ServiceProvider;

final class ImportServiceProvider extends ServiceProvider
{
    private const array BINDINGS = [
        KitApplicabilityImportInterface::class => KitApplicabilityImport::class,
        WarehouseKitClientInterface::class => WarehouseKitClient::class,
        VehiclesModificationClientInterface::class => VehiclesModificationClient::class,
        KitApplicabilityCommandInterface::class => KitApplicabilityCommand::class,
        ImportKitApplicabilityRowServiceInterface::class => ImportKitApplicabilityRowService::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
