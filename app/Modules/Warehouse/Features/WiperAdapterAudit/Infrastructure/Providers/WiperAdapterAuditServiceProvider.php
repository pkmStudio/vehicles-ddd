<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\WiperAdapterAudit\Infrastructure\Providers;

use App\Modules\Warehouse\Features\WiperAdapterAudit\Application\Services\WiperAdapterAuditService;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Repositories\WiperAdapterAuditKitRepositoryInterface;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Domain\Contracts\Services\WiperAdapterAuditServiceInterface;
use App\Modules\Warehouse\Features\WiperAdapterAudit\Infrastructure\Repositories\WiperAdapterAuditKitRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует DI-биндинги фичи Warehouse WiperAdapterAudit.
 */
final class WiperAdapterAuditServiceProvider extends ServiceProvider
{
    /**
     * Биндит repository и application-сервис аудита адаптеров.
     * Шаги:
     * 1) Зарегистрировать repository interface на Eloquent adapter.
     * 2) Зарегистрировать service interface на application service.
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
