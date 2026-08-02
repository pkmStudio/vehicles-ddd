<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Providers;

use App\Modules\Shared\Application\UseCases\PublishLocalImportRequestUseCase;
use App\Modules\Shared\Domain\Contracts\Files\LocalImportFileStorageInterface;
use App\Modules\Shared\Domain\Contracts\Publishers\LocalImportRequestPublisherInterface;
use App\Modules\Shared\Domain\Contracts\UseCases\PublishLocalImportRequestUseCaseInterface;
use App\Modules\Shared\Infrastructure\Files\LaravelLocalImportFileStorage;
use App\Modules\Shared\Infrastructure\Publishers\RabbitMqLocalImportRequestPublisher;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует общие технические биндинги Shared module.
 */
final class SharedServiceProvider extends ServiceProvider
{
    /**
     * Регистрирует порты локальной публикации import request.
     */
    public function register(): void
    {
        $this->app->bind(
            abstract: LocalImportFileStorageInterface::class,
            concrete: LaravelLocalImportFileStorage::class,
        );

        $this->app->bind(
            abstract: LocalImportRequestPublisherInterface::class,
            concrete: RabbitMqLocalImportRequestPublisher::class,
        );

        $this->app->bind(
            abstract: PublishLocalImportRequestUseCaseInterface::class,
            concrete: PublishLocalImportRequestUseCase::class,
        );
    }
}
