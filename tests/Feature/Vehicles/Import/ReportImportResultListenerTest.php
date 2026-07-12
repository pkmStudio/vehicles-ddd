<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Vehicles\Import\Application\Listeners\ReportImportResultListener;
use App\Vehicles\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Vehicles\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Vehicles\Import\Domain\Events\Vehicle\VehicleImportCompleted;
use App\Vehicles\Import\Domain\Enums\ImportCompletionStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * Регрессионный тест на plan.md §6.1 для ReportImportResultListener. FileNotificationService
 * мокается — реальная реализация публикует в RabbitMQ, которого в тестах нет.
 */
final class ReportImportResultListenerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет ветку «есть ошибки»: слушатель выгружает CSV-отчёт на диск и уведомляет
     * инициатора статусом CompletedWithErrors с путём к отчёту.
     *
     * Шаги:
     * 1. Подменяет диск 'exports' на фейковый и кладёт в cache одну построчную ошибку импорта.
     * 2. Мокает FileNotificationServiceInterface — ожидает notifyImportCompleted() с
     *    userId/runId/errorsCount и путём, начинающимся с 'exports/import-failures'.
     * 3. Зовёт handle() с VehicleImportCompleted(userId: 42, cacheKey, runId: 'run-123').
     * 4. Проверяет, что cache-запись с ошибками снята после обработки.
     */
    public function test_exports_failures_and_notifies_user(): void
    {
        Storage::fake('exports');

        $cacheKey = 'report_listener_test_failures';
        Cache::put($cacheKey, [
            ['row' => 2, 'attribute' => 'name', 'errors' => ['обязательное поле'], 'values' => ['mfa_id' => 10]],
        ]);

        $notifier = $this->mock(FileNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyImportCompleted')
            ->once()
            ->with(
                Mockery::on(
                    fn (ImportCompletionNotificationDTO $payload) => $payload->userId === 42
                        && $payload->status === ImportCompletionStatusEnum::CompletedWithErrors
                        && $payload->runId === 'run-123'
                        && $payload->errorsCount === 1
                        && is_string($payload->path)
                        && str_starts_with($payload->path, 'exports/import-failures'),
                ),
            );

        $listener = app(ReportImportResultListener::class);
        $listener->handle(new VehicleImportCompleted(userId: 42, cacheKey: $cacheKey, runId: 'run-123'));

        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Проверяет ветку «ошибок нет»: слушатель не строит отчёт, уведомляет статусом Completed
     * без пути к файлу.
     *
     * Шаги:
     * 1. Не кладёт в cache никаких ошибок для данного cacheKey.
     * 2. Мокает FileNotificationServiceInterface — ожидает notifyImportCompleted() со статусом
     *    Completed, errorsCount=0 и path=null.
     * 3. Зовёт handle() с VehicleImportCompleted(userId: 42, cacheKey, runId: 'run-456').
     * 4. Проверяет, что cache-ключ (которого и так не было) остаётся отсутствующим.
     */
    public function test_does_not_notify_when_no_failures(): void
    {
        $cacheKey = 'report_listener_test_no_failures';

        $notifier = $this->mock(FileNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyImportCompleted')
            ->once()
            ->with(
                Mockery::on(
                    fn (ImportCompletionNotificationDTO $payload) => $payload->userId === 42
                        && $payload->status === ImportCompletionStatusEnum::Completed
                        && $payload->runId === 'run-456'
                        && $payload->errorsCount === 0
                        && $payload->path === null,
                ),
            );

        $listener = app(ReportImportResultListener::class);
        $listener->handle(new VehicleImportCompleted(userId: 42, cacheKey: $cacheKey, runId: 'run-456'));

        $this->assertFalse(Cache::has($cacheKey));
    }
}
