<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Vehicles\Import\Domain\Contracts\Imports\External\VehicleMultiSheetImportInterface;
use App\Vehicles\Import\Domain\Contracts\Services\External\CleanupExternalImportFileServiceInterface;
use App\Vehicles\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Vehicles\Import\Domain\DTOs\ImportRunContextDTO;
use App\Vehicles\Import\Infrastructure\Messaging\Handlers\ImportFileRequestedHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class ImportFileRequestedHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_starts_vehicle_multi_sheet_import_from_files_disk(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('VehicleMultiSheet/vehicles.xlsx', 'xlsx');

        $import = $this->mock(VehicleMultiSheetImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'VehicleMultiSheet/vehicles.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->runId === 'run-123'),
                's3',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-123',
            'import_type' => 'vehicle_multi_sheet',
            'path' => 'VehicleMultiSheet/vehicles.xlsx',
        ]);

        $this->assertTrue(Cache::has($this->cleanupCacheKey('run-123')));
    }

    public function test_invalid_payload_is_logged_and_skipped(): void
    {
        $useCase = $this->mock(StartExternalFileImportUseCaseInterface::class);
        $useCase->shouldNotReceive('execute');

        Log::shouldReceive('error')
            ->once()
            ->with(
                'RabbitMQ: Import file request payload validation failed',
                Mockery::on(fn (array $context): bool => in_array('path', $context['invalid_keys'] ?? [], true)),
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-123',
            'import_type' => 'vehicle_multi_sheet',
        ]);

        $this->assertFalse(Cache::has($this->cleanupCacheKey('run-123')));
    }

    public function test_duplicate_run_id_is_skipped(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('VehicleMultiSheet/vehicles.xlsx', 'xlsx');

        $import = $this->mock(VehicleMultiSheetImportInterface::class);
        $import->shouldReceive('import')->once();

        $payload = [
            'user_id' => 42,
            'run_id' => 'run-123',
            'import_type' => 'vehicle_multi_sheet',
            'path' => 'VehicleMultiSheet/vehicles.xlsx',
        ];

        app(ImportFileRequestedHandler::class)->handle($payload);
        app(ImportFileRequestedHandler::class)->handle($payload);
    }

    public function test_cleanup_deletes_source_file(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('VehicleMultiSheet/vehicles.xlsx', 'xlsx');

        Cache::put($this->cleanupCacheKey('run-cleanup'), [
            'disk' => 's3',
            'path' => 'VehicleMultiSheet/vehicles.xlsx',
        ], now()->addMinute());

        app(CleanupExternalImportFileServiceInterface::class)->cleanup('run-cleanup');

        Storage::disk('s3')->assertMissing('VehicleMultiSheet/vehicles.xlsx');
        $this->assertFalse(Cache::has($this->cleanupCacheKey('run-cleanup')));
    }

    public function test_import_file_events_have_unique_names_and_same_handler(): void
    {
        $handler = [ImportFileRequestedHandler::class, 'handle'];

        $this->assertSame($handler, config('rabbit-transport.inbound.VEHICLES_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.ENGINES_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.MODIFICATIONS_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.ENGINE_GROUPS_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.SPARK_PLUGS_IMPORT_FILE_REQUESTED'));

        $this->assertSame([
            'crm.vehicles.import',
            'crm.engines.import',
            'crm.modifications.import',
            'crm.engine-groups.import',
            'crm.spark-plugs.import',
        ], config('rabbit-transport.setup.bindings'));
    }

    private function cleanupCacheKey(string $runId): string
    {
        return sprintf((string) config('vehicles-import.external.cache.keys.cleanup'), $runId);
    }
}
