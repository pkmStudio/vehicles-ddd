<?php

declare(strict_types=1);

namespace App\Warehouse\WiperAdapterAudit\Infrastructure\Providers;

use App\Warehouse\WiperAdapterAudit\Application\Services\WiperAdapterAuditService;
use App\Warehouse\WiperAdapterAudit\Domain\Contracts\Repositories\WiperAdapterAuditKitRepositoryInterface;
use App\Warehouse\WiperAdapterAudit\Domain\Contracts\Services\WiperAdapterAuditServiceInterface;
use App\Warehouse\WiperAdapterAudit\Infrastructure\Repositories\WiperAdapterAuditKitRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги фичи Warehouse WiperAdapterAudit.
 */
final class WiperAdapterAuditServiceProvider extends ServiceProvider
{
    /**
     * Биндит repository и application-сервис аудита адаптеров.
     */
    public function register(): void
    {
        $this->app->bind(
            abstract: WiperAdapterAuditKitRepositoryInterface::class,
            concrete: WiperAdapterAuditKitRepository::class,
        );
        $this->app->bind(
            abstract: WiperAdapterAuditServiceInterface::class,
            concrete: WiperAdapterAuditService::class,
        );
    }
}
