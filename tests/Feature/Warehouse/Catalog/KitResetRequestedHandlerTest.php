<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Catalog;

use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Commands\KitCommandInterface;
use App\Modules\Warehouse\Features\Catalog\Domain\Contracts\Notifications\KitResetNotificationServiceInterface;
use App\Modules\Warehouse\Features\Catalog\Infrastructure\Messaging\Handlers\KitResetRequestedHandler;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Проверяет RabbitMQ handler bulk-сброса Warehouse-наборов.
 */
final class KitResetRequestedHandlerTest extends TestCase
{
    /**
     * Проверяет, что валидный reset-request запускает command и completed-result.
     */
    public function test_resets_kits_and_notifies_completion(): void
    {
        $kits = $this->mock(KitCommandInterface::class);
        $kits->shouldReceive('reset')->once();

        $notifier = $this->mock(KitResetNotificationServiceInterface::class);
        $notifier->shouldReceive('completed')
            ->once()
            ->with(42, 'reset-123');
        $notifier->shouldReceive('failed')->never();

        app(KitResetRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'reset-123',
        ]);
    }

    /**
     * Проверяет, что невалидный reset-request логируется и не запускает command.
     */
    public function test_invalid_payload_is_logged_and_skipped(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(
                'RabbitMQ: Warehouse kits reset payload validation failed',
                Mockery::on(fn (array $context): bool => in_array('user_id', $context['invalid_keys'] ?? [], true)),
            );

        $kits = $this->mock(KitCommandInterface::class);
        $kits->shouldReceive('reset')->never();

        $notifier = $this->mock(KitResetNotificationServiceInterface::class);
        $notifier->shouldReceive('completed')->never();
        $notifier->shouldReceive('failed')->never();

        app(KitResetRequestedHandler::class)->handle([
            'operation_id' => 'reset-123',
        ]);
    }

    /**
     * Проверяет, что техническая ошибка сброса публикует failed-result и пробрасывается дальше.
     */
    public function test_reset_failure_notifies_failure_and_rethrows(): void
    {
        $exception = new RuntimeException('reset failed');

        $kits = $this->mock(KitCommandInterface::class);
        $kits->shouldReceive('reset')->once()->andThrow($exception);

        $notifier = $this->mock(KitResetNotificationServiceInterface::class);
        $notifier->shouldReceive('completed')->never();
        $notifier->shouldReceive('failed')
            ->once()
            ->with(42, 'reset-123');

        $this->expectExceptionObject($exception);

        app(KitResetRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'reset-123',
        ]);
    }
}
