<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Import;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Imports\External\VehicleMultiSheetImportInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\External\CleanupExternalImportFileServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Vehicles\Features\Import\Domain\DTOs\ImportRunContextDTO;
use App\Modules\Vehicles\Features\Import\Infrastructure\Messaging\Handlers\ImportFileRequestedHandler;
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

    /**
     * Проверяет happy-path обработчика: валидный payload → реальный (не мокнутый) UseCase/
     * фабрика/идемпотентность → нужный импортный адаптер вызван с нужными аргументами,
     * плюс инструкция очистки входного файла сохранена в cache.
     *
     * Шаги:
     * 1. Подменяет files_disk на фейковый 's3' и кладёт туда файл по ожидаемому пути.
     * 2. Мокает VehicleMultiSheetImportInterface — ожидает import() с этим путём, диском 's3'
     *    и ImportRunContextDTO(userId: 42, operationId: 'run-123').
     * 3. Зовёт handle() с валидным payload (import_type=vehicle_multi_sheet).
     * 4. Проверяет, что cache-ключ отложенной очистки для этого operationId создан.
     */
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
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->operationId === 'run-123'),
                's3',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-123',
            'import_type' => 'vehicle_multi_sheet',
            'path' => 'VehicleMultiSheet/vehicles.xlsx',
        ]);

        $this->assertTrue(Cache::has($this->cleanupCacheKey('run-123')));
    }

    public function test_payload_disk_overrides_default_files_disk(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('local');
        Storage::disk('local')->put('VehicleMultiSheet/vehicles.xlsx', 'xlsx');

        $import = $this->mock(VehicleMultiSheetImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'VehicleMultiSheet/vehicles.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->operationId === 'run-local'),
                'local',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-local',
            'import_type' => 'vehicle_multi_sheet',
            'disk' => 'local',
            'path' => 'VehicleMultiSheet/vehicles.xlsx',
        ]);

        $this->assertTrue(Cache::has($this->cleanupCacheKey('run-local')));
    }

    public function test_cleanup_can_be_disabled_for_local_console_requests(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('local');
        Storage::disk('local')->put('VehicleMultiSheet/vehicles.xlsx', 'xlsx');

        $import = $this->mock(VehicleMultiSheetImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'VehicleMultiSheet/vehicles.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42 && $context->operationId === 'run-keep-local'),
                'local',
            );

        app(ImportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'operation_id' => 'run-keep-local',
            'import_type' => 'vehicle_multi_sheet',
            'disk' => 'local',
            'path' => 'VehicleMultiSheet/vehicles.xlsx',
            'cleanup_after_import' => false,
        ]);

        $this->assertFalse(Cache::has($this->cleanupCacheKey('run-keep-local')));
    }

    /**
     * Проверяет, что невалидный payload (нет обязательного поля path) не доходит до UseCase —
     * ошибка валидации логируется, а не бросается исключением (брокер не должен ретраить
     * по бизнес-невалидности).
     *
     * Шаги:
     * 1. Мокает StartExternalFileImportUseCaseInterface — ожидает, что execute() НЕ вызовется.
     * 2. Ожидает Log::error с указанием invalid_keys, включающим 'path'.
     * 3. Зовёт handle() с payload без поля 'path'.
     * 4. Проверяет, что cache-ключ отложенной очистки не создан (значит, до UseCase не дошло).
     */
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
            'operation_id' => 'run-123',
            'import_type' => 'vehicle_multi_sheet',
        ]);

        $this->assertFalse(Cache::has($this->cleanupCacheKey('run-123')));
    }

    /**
     * Проверяет идемпотентность по operationId: повторная доставка одного и того же сообщения
     * (например, ретрай брокера) не запускает импорт дважды.
     *
     * Шаги:
     * 1. Мокает VehicleMultiSheetImportInterface — ожидает import() ровно один раз.
     * 2. Дважды зовёт handle() с одинаковым payload (тот же operation_id).
     * 3. Mockery сам провалит тест, если import() будет вызван больше одного раза.
     */
    public function test_duplicate_operation_id_is_skipped(): void
    {
        config(['filesystems.files_disk' => 's3']);
        Storage::fake('s3');
        Storage::disk('s3')->put('VehicleMultiSheet/vehicles.xlsx', 'xlsx');

        $import = $this->mock(VehicleMultiSheetImportInterface::class);
        $import->shouldReceive('import')->once();

        $payload = [
            'user_id' => 42,
            'operation_id' => 'run-123',
            'import_type' => 'vehicle_multi_sheet',
            'path' => 'VehicleMultiSheet/vehicles.xlsx',
        ];

        app(ImportFileRequestedHandler::class)->handle($payload);
        app(ImportFileRequestedHandler::class)->handle($payload);
    }

    /**
     * Проверяет CleanupExternalImportFileService: по сохранённой в cache инструкции удаляет
     * исходный файл на нужном диске и снимает саму инструкцию.
     *
     * Шаги:
     * 1. Кладёт файл на фейковый диск 's3' и заранее пишет cache-инструкцию очистки для operationId.
     * 2. Зовёт cleanup(operationId) напрямую (не через Handler).
     * 3. Проверяет, что файл удалён с диска.
     * 4. Проверяет, что cache-инструкция очистки тоже снята (не остаётся мусора).
     */
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

    /**
     * Проверяет саму проводку config/rabbit-transport.php: пять разных inbound-имён событий
     * (по одному на тип импорта) должны быть забинжены на один и тот же Handler — конкретный
     * адаптер выбирается уже внутри по import_type, а не через отдельные Handler-классы.
     *
     * Шаги:
     * 1. Проверяет, что все 5 inbound-записей (VEHICLES/ENGINES/MODIFICATIONS/ENGINE_GROUPS/
     *    SPARK_PLUGS _IMPORT_FILE_REQUESTED) указывают на [ImportFileRequestedHandler, 'handle'].
     * 2. Проверяет, что routing-key bindings в setup.bindings содержат эти же пять типов
     *    (не строгое равенство — конфиг общий на всё приложение, Export добавляет туда свои
     *    bindings, тест проверяет именно вклад Import, а не запрещает чужие записи).
     */
    public function test_import_file_events_have_unique_names_and_same_handler(): void
    {
        $handler = [ImportFileRequestedHandler::class, 'handle'];

        $this->assertSame($handler, config('rabbit-transport.inbound.VEHICLES_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.ENGINES_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.MODIFICATIONS_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.ENGINE_GROUPS_IMPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.SPARK_PLUGS_IMPORT_FILE_REQUESTED'));

        $bindings = (array) config('rabbit-transport.setup.bindings');

        $this->assertContains('crm.vehicles.import', $bindings);
        $this->assertContains('crm.engines.import', $bindings);
        $this->assertContains('crm.modifications.import', $bindings);
        $this->assertContains('crm.engine-groups.import', $bindings);
        $this->assertContains('crm.spark-plugs.import', $bindings);
    }

    private function cleanupCacheKey(string $operationId): string
    {
        return sprintf((string) config('vehicles.import.external.cache.keys.cleanup'), $operationId);
    }
}
