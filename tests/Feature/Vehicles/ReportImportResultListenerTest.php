<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Application\Listeners\ReportImportResultListener;
use App\Vehicles\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Vehicles\Import\Domain\Events\Vehicle\VehicleImportCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для ReportImportResultListener. FileNotificationService
 * мокается — реальная реализация публикует в RabbitMQ, которого в тестах нет.
 */
final class ReportImportResultListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_failures_and_notifies_user(): void
    {
        Storage::fake('exports');

        $cacheKey = 'report_listener_test_failures';
        Cache::put($cacheKey, [
            ['row' => 2, 'attribute' => 'name', 'errors' => ['обязательное поле'], 'values' => ['mfa_id' => 10]],
        ]);

        $notifier = $this->mock(FileNotificationServiceInterface::class);
        $notifier->shouldReceive('send')
            ->once()
            ->with(42, \Mockery::on(fn (string $path) => str_starts_with($path, 'exports/import-failures')));

        $listener = app(ReportImportResultListener::class);
        $listener->handle(new VehicleImportCompleted(42, $cacheKey));

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_does_not_notify_when_no_failures(): void
    {
        $cacheKey = 'report_listener_test_no_failures';

        $notifier = $this->mock(FileNotificationServiceInterface::class);
        $notifier->shouldNotReceive('send');

        $listener = app(ReportImportResultListener::class);
        $listener->handle(new VehicleImportCompleted(42, $cacheKey));

        $this->assertFalse(Cache::has($cacheKey));
    }
}
