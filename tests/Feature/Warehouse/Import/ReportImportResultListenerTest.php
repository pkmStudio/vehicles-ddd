<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Warehouse\Import\Application\Listeners\ReportImportResultListener;
use App\Warehouse\Import\Domain\Contracts\Notifications\ImportNotificationServiceInterface;
use App\Warehouse\Import\Domain\DTOs\ImportCompletionNotificationDTO;
use App\Warehouse\Import\Domain\Enums\ImportCompletionStatusEnum;
use App\Warehouse\Import\Domain\Enums\ImportTypeEnum;
use App\Warehouse\Import\Domain\Events\NomenclatureImportCompleted;
use App\Warehouse\Import\Domain\Events\PackDimensionImportCompleted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class ReportImportResultListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_reports_failures_and_notifies_user(): void
    {
        $cacheKey = 'nomenclature_import_failures_test-run';
        Cache::put($cacheKey, [
            ['row' => 2, 'attribute' => 'артикул X', 'errors' => ['Тип не найден'], 'values' => ['type' => 'X']],
        ]);

        $notifier = $this->mock(ImportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyImportCompleted')
            ->once()
            ->with(Mockery::on(function (ImportCompletionNotificationDTO $payload): bool {
                return $payload->status === ImportCompletionStatusEnum::Failed
                    && $payload->importType === ImportTypeEnum::Nomenclature
                    && $payload->userId === 42
                    && $payload->runId === 'test-run'
                    && is_string($payload->failuresReportPath)
                    && str_starts_with($payload->failuresReportPath, 'exports/warehouse-import-failures');
            }));

        app(ReportImportResultListener::class)->handle(
            new NomenclatureImportCompleted(userId: 42, cacheKey: $cacheKey, runId: 'test-run'),
        );

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_no_failures_reports_completed_without_path(): void
    {
        $cacheKey = 'pack_dimension_import_failures_test-run-2';

        $notifier = $this->mock(ImportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyImportCompleted')
            ->once()
            ->with(Mockery::on(function (ImportCompletionNotificationDTO $payload): bool {
                return $payload->status === ImportCompletionStatusEnum::Completed
                    && $payload->importType === ImportTypeEnum::PackDimension
                    && $payload->failuresReportPath === null;
            }));

        app(ReportImportResultListener::class)->handle(
            new PackDimensionImportCompleted(userId: null, cacheKey: $cacheKey, runId: null),
        );
    }
}
