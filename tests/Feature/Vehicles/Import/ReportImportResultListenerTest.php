<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Application\Listeners\ReportImportResultListener;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Notifications\FileNotificationServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ExternalImportTypeEnum;
use App\Modules\Vehicles\Features\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineCrossImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Engine\EngineImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Manufacturer\ManufacturerImportCompleted;
use App\Modules\Vehicles\Features\Import\Domain\Events\Vehicle\VehicleImportCompleted;
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
     * 1. Подменяет disk 's3' на фейковый и кладёт в cache одну построчную ошибку импорта.
     * 2. Мокает FileNotificationServiceInterface — ожидает notifyImportCompleted() с
     *    userId/operationId/errorsCount, disk='s3' и путём в 'dan-vehicles/import'.
     * 3. Зовёт handle() с VehicleImportCompleted(userId: 42, cacheKey, operationId: 'run-123').
     * 4. Проверяет, что cache-запись с ошибками снята после обработки.
     */
    public function test_exports_failures_and_notifies_user(): void
    {
        config([
            'vehicles.import.failures.disk' => 's3',
            'vehicles.import.failures.directory' => 'dan-vehicles/import',
        ]);
        Storage::fake('s3');

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
                        && $payload->importType === ExternalImportTypeEnum::VehicleMultiSheet
                        && $payload->operationId === 'run-123'
                        && $payload->disk === 's3'
                        && $payload->errorsCount === 1
                        && is_string($payload->path)
                        && str_starts_with($payload->path, 'dan-vehicles/import/import-failures')
                        && str_ends_with($payload->path, '.xlsx'),
                ),
            );

        $listener = app(ReportImportResultListener::class);
        $listener->handle(new VehicleImportCompleted(userId: 42, cacheKey: $cacheKey, operationId: 'run-123'));

        $this->assertFalse(Cache::has($cacheKey));

        $files = Storage::disk('s3')->files('dan-vehicles/import');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.xlsx', $files[0]);
    }

    public function test_failure_notification_payload_contains_report_disk_aliases(): void
    {
        $payload = new ImportCompletionNotificationDTO(
            userId: 42,
            status: ImportCompletionStatusEnum::CompletedWithErrors,
            importType: ExternalImportTypeEnum::VehicleMultiSheet,
            operationId: 'run-123',
            disk: 's3',
            errorsCount: 1,
            path: 'dan-vehicles/import/import-failures.csv',
        );

        $this->assertSame([
            'user_id' => 42,
            'operation_id' => 'run-123',
            'status' => 'completed_with_errors',
            'import_type' => 'vehicle_multi_sheet',
            'disk' => 's3',
            'errors_count' => 1,
            'path' => 'dan-vehicles/import/import-failures.csv',
            'failures_report_path' => 'dan-vehicles/import/import-failures.csv',
            'failures_report_disk' => 's3',
        ], $payload->toArray());
    }

    /**
     * Проверяет ветку «ошибок нет»: слушатель не строит отчёт, уведомляет статусом Completed
     * без пути к файлу.
     *
     * Шаги:
     * 1. Не кладёт в cache никаких ошибок для данного cacheKey.
     * 2. Мокает FileNotificationServiceInterface — ожидает notifyImportCompleted() со статусом
     *    Completed, errorsCount=0 и path=null.
     * 3. Зовёт handle() с VehicleImportCompleted(userId: 42, cacheKey, operationId: 'run-456').
     * 4. Проверяет, что cache-ключ (которого и так не было) остаётся отсутствующим.
     */
    public function test_does_not_notify_when_no_failures(): void
    {
        config(['vehicles.import.failures.disk' => 's3']);

        $cacheKey = 'report_listener_test_no_failures';

        $notifier = $this->mock(FileNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyImportCompleted')
            ->once()
            ->with(
                Mockery::on(
                    fn (ImportCompletionNotificationDTO $payload) => $payload->userId === 42
                        && $payload->status === ImportCompletionStatusEnum::Completed
                        && $payload->importType === ExternalImportTypeEnum::VehicleMultiSheet
                        && $payload->operationId === 'run-456'
                        && $payload->disk === null
                        && $payload->errorsCount === 0
                        && $payload->path === null,
                ),
            );

        $listener = app(ReportImportResultListener::class);
        $listener->handle(new VehicleImportCompleted(userId: 42, cacheKey: $cacheKey, operationId: 'run-456'));

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_completion_event_type_is_mapped_to_import_type(): void
    {
        $publishedTypes = [];

        $notifier = $this->mock(FileNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyImportCompleted')
            ->times(4)
            ->with(Mockery::on(function (ImportCompletionNotificationDTO $payload) use (&$publishedTypes): bool {
                $publishedTypes[] = $payload->importType;

                return $payload->status === ImportCompletionStatusEnum::Completed;
            }));

        $listener = app(ReportImportResultListener::class);
        $listener->handle(new VehicleImportCompleted(userId: 42, cacheKey: 'vehicle-empty', operationId: 'vehicle-run'));
        $listener->handle(new EngineImportCompleted(userId: 42, cacheKey: 'engine-empty', operationId: 'engine-run'));
        $listener->handle(new EngineCrossImportCompleted(userId: 42, cacheKey: 'engine-cross-empty', operationId: 'engine-cross-run'));
        $listener->handle(new ManufacturerImportCompleted(userId: 42, cacheKey: 'manufacturer-empty', operationId: 'manufacturer-run'));

        $this->assertSame([
            ExternalImportTypeEnum::VehicleMultiSheet,
            ExternalImportTypeEnum::EngineMultiSheet,
            ExternalImportTypeEnum::EngineCross,
            ExternalImportTypeEnum::Manufacturer,
        ], $publishedTypes);
    }
}
