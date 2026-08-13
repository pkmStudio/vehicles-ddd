<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse\Import;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\KitImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\NomenclatureImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\Imports\PackDimensionImportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Warehouse\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Warehouse\Features\Import\Infrastructure\Messaging\Handlers\ImportFileRequestedHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Warehouse\Features\Import\DTO\ImportFileRequested as WireImportFileRequested;
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

    public function test_starts_nomenclature_import_from_files_disk(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('warehouse/nomenclature.xlsx', 'xlsx');

        $import = $this->mock(NomenclatureImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'warehouse/nomenclature.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->operationId === 'run-123'),
                's3',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-123',
            'import_type' => 'nomenclature',
            'path' => 'warehouse/nomenclature.xlsx',
        ]);

        $this->assertTrue(Cache::has($this->cleanupCacheKey('run-123')));
    }

    public function test_accepts_published_wire_import_request_payload(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('warehouse/nomenclature.xlsx', 'xlsx');

        $import = $this->mock(NomenclatureImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'warehouse/nomenclature.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42
                    && $context->operationId === 'wire-import-warehouse'),
                's3',
            );

        $message = new WireImportFileRequested(
            userId: 42,
            operationId: 'wire-import-warehouse',
            importType: 'nomenclature',
            disk: 's3',
            path: 'warehouse/nomenclature.xlsx',
        );

        app(ImportFileRequestedHandler::class)->handle($message->toArray());

        $this->assertTrue(Cache::has($this->cleanupCacheKey('wire-import-warehouse')));
    }

    public function test_payload_disk_overrides_default_files_disk(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('local');
        Storage::disk('local')->put('warehouse/nomenclature.xlsx', 'xlsx');

        $import = $this->mock(NomenclatureImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'warehouse/nomenclature.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->operationId === 'run-local'),
                'local',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-local',
            'import_type' => 'nomenclature',
            'disk' => 'local',
            'path' => 'warehouse/nomenclature.xlsx',
        ]);

        $this->assertTrue(Cache::has($this->cleanupCacheKey('run-local')));
    }

    public function test_cleanup_can_be_disabled_for_local_console_requests(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('local');
        Storage::disk('local')->put('warehouse/nomenclature.xlsx', 'xlsx');

        $import = $this->mock(NomenclatureImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'warehouse/nomenclature.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->operationId === 'run-keep-local'),
                'local',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-keep-local',
            'import_type' => 'nomenclature',
            'disk' => 'local',
            'path' => 'warehouse/nomenclature.xlsx',
            'cleanup_after_import' => false,
        ]);

        $this->assertFalse(Cache::has($this->cleanupCacheKey('run-keep-local')));
    }

    public function test_invalid_payload_is_logged_and_skipped(): void
    {
        $useCase = $this->mock(StartExternalFileImportUseCaseInterface::class);
        $useCase->shouldNotReceive('execute');

        Log::shouldReceive('error')
            ->once()
            ->with(
                'RabbitMQ: Warehouse import file request payload validation failed',
                Mockery::on(fn (array $context): bool => in_array('path', $context['invalid_keys'] ?? [], true)),
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-123',
            'import_type' => 'nomenclature',
        ]);
    }

    public function test_duplicate_operation_id_is_skipped(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('warehouse/pd.xlsx', 'xlsx');

        $import = $this->mock(PackDimensionImportInterface::class);
        $import->shouldReceive('import')->once();

        $payload = [
            'user_id' => 42,
            'operation_id' => 'run-dup',
            'import_type' => 'pack_dimension',
            'path' => 'warehouse/pd.xlsx',
        ];

        app(ImportFileRequestedHandler::class)->handle($payload);
        app(ImportFileRequestedHandler::class)->handle($payload);
    }

    public function test_starts_kit_import_from_files_disk(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('warehouse/kit.xlsx', 'xlsx');

        $import = $this->mock(KitImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'warehouse/kit.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->operationId === 'run-kit'),
                's3',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-kit',
            'import_type' => 'kit',
            'path' => 'warehouse/kit.xlsx',
        ]);
    }

    public function test_import_file_events_are_registered(): void
    {
        $handler = [ImportFileRequestedHandler::class, 'handle'];

        $this->assertSame($handler, config('rabbit-transport.inbound.WAREHOUSE_NOMENCLATURE_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.WAREHOUSE_PACK_DIMENSION_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.WAREHOUSE_KIT_IMPORT_FILE_REQUESTED'));
        $this->assertSame('warehouse.import.completed', config('rabbit-transport.outbound.WAREHOUSE_IMPORT_COMPLETED'));

        $bindings = (array) config('rabbit-transport.setup.bindings');

        $this->assertContains('crm.warehouse.nomenclatures.import', $bindings);
        $this->assertContains('crm.warehouse.pack-dimensions.import', $bindings);
        $this->assertContains('crm.warehouse.kits.import', $bindings);
    }

    private function cleanupCacheKey(string $operationId): string
    {
        return sprintf((string) config('warehouse.import.external.cache.keys.cleanup'), $operationId);
    }
}
