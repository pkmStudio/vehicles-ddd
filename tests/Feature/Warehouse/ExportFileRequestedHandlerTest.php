<?php

declare(strict_types=1);

namespace Tests\Feature\Warehouse;

use App\Warehouse\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Warehouse\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Warehouse\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Warehouse\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Warehouse\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Warehouse\Export\Domain\DTOs\ExportRunContextDTO;
use App\Warehouse\Export\Domain\DTOs\KitExportFiltersDTO;
use App\Warehouse\Export\Domain\DTOs\KitExportSortDTO;
use App\Warehouse\Export\Domain\Enums\ExportCompletionStatusEnum;
use App\Warehouse\Export\Domain\Enums\ExportTypeEnum;
use App\Warehouse\Export\Infrastructure\Messaging\Handlers\ExportFileRequestedHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Проверяет RabbitMQ handler внешних запросов Warehouse-экспорта.
 */
final class ExportFileRequestedHandlerTest extends TestCase
{
    /**
     * Подготавливает cache перед каждым тестом идемпотентности.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /**
     * Очищает cache после тестов handler'а.
     */
    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    /**
     * Проверяет успешный запуск экспорта номенклатуры и completed-уведомление.
     */
    public function test_starts_nomenclature_export_and_notifies_completion(): void
    {
        $adapter = Mockery::mock(FileExportInterface::class);
        $adapter->shouldReceive('export')
            ->once()
            ->with(
                Mockery::on(fn (ExportRunContextDTO $context): bool => $context->userId === 42 && $context->runId === 'run-123'),
                'local',
            )
            ->andReturn('exports/warehouse-nomenclature-type-2-run-123.xlsx');

        $factory = $this->mock(ExportFileFactoryInterface::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(ExportTypeEnum::NomenclatureByType, 2, null, null)
            ->andReturn($adapter);

        $notifier = $this->mock(ExportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyExportCompleted')
            ->once()
            ->with(Mockery::on(function (ExportCompletionNotificationDTO $payload): bool {
                return $payload->userId === 42
                    && $payload->status === ExportCompletionStatusEnum::Completed
                    && $payload->runId === 'run-123'
                    && $payload->exportType === ExportTypeEnum::NomenclatureByType
                    && $payload->typeId === 2
                    && $payload->path === 'exports/warehouse-nomenclature-type-2-run-123.xlsx';
            }));

        app(ExportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-123',
            'export_type' => 'nomenclature_by_type',
            'type_id' => 2,
        ]);
    }

    /**
     * Проверяет, что запрос номенклатуры без type_id логируется и не запускает UseCase.
     */
    public function test_nomenclature_export_without_type_id_is_logged_and_skipped(): void
    {
        $useCase = $this->mock(StartExportUseCaseInterface::class);
        $useCase->shouldNotReceive('execute');

        Log::shouldReceive('error')
            ->once()
            ->with(
                'RabbitMQ: Warehouse export file request payload validation failed',
                Mockery::on(fn (array $context): bool => in_array('type_id', $context['invalid_keys'] ?? [], true)),
            );

        app(ExportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-123',
            'export_type' => 'nomenclature_by_type',
        ]);
    }

    /**
     * Проверяет, что повтор одного runId не запускает второй экспорт.
     */
    public function test_duplicate_run_id_is_skipped(): void
    {
        $adapter = Mockery::mock(FileExportInterface::class);
        $adapter->shouldReceive('export')->once()->andReturn('exports/warehouse-kits-run-dup.xlsx');

        $factory = $this->mock(ExportFileFactoryInterface::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(
                ExportTypeEnum::Kit,
                null,
                Mockery::type(KitExportFiltersDTO::class),
                Mockery::type(KitExportSortDTO::class),
            )
            ->andReturn($adapter);

        $notifier = $this->mock(ExportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyExportCompleted')->once();

        $payload = [
            'user_id' => 42,
            'run_id' => 'run-dup',
            'export_type' => 'kit',
        ];

        app(ExportFileRequestedHandler::class)->handle($payload);
        app(ExportFileRequestedHandler::class)->handle($payload);
    }

    /**
     * Проверяет передачу Kit-фильтров и сортировки из payload в selector-фабрику.
     */
    public function test_kit_export_passes_filters_and_sort_to_factory(): void
    {
        $adapter = Mockery::mock(FileExportInterface::class);
        $adapter->shouldReceive('export')->once()->andReturn('exports/warehouse-kits-filtered.xlsx');

        $factory = $this->mock(ExportFileFactoryInterface::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(
                ExportTypeEnum::Kit,
                null,
                Mockery::on(function (KitExportFiltersDTO $filters): bool {
                    return $filters->ids === [10, 20]
                        && $filters->typeIds === [2]
                        && $filters->isActive === true
                        && $filters->isSaleSeparately === false
                        && $filters->nomenclaturePartNumbers === ['A1', 'B2']
                        && $filters->search === 'adapter';
                }),
                Mockery::on(function (KitExportSortDTO $sort): bool {
                    return $sort->field === 'type_id'
                        && $sort->direction === 'desc';
                }),
            )
            ->andReturn($adapter);

        $notifier = $this->mock(ExportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyExportCompleted')->once();

        app(ExportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-filters',
            'export_type' => 'kit',
            'filters' => [
                'ids' => [10, 20],
                'type_ids' => [2],
                'is_active' => true,
                'is_sale_separately' => false,
                'nomenclature_part_numbers' => ['A1', 'B2'],
                'search' => 'adapter',
            ],
            'sort' => [
                'field' => 'type_id',
                'direction' => 'desc',
            ],
        ]);
    }

    /**
     * Проверяет запуск отчёта аудита адаптеров через общий Warehouse Export handler.
     */
    public function test_starts_wiper_adapter_audit_export(): void
    {
        $adapter = Mockery::mock(FileExportInterface::class);
        $adapter->shouldReceive('export')->once()->andReturn('exports/warehouse-wiper-adapter-audit.xlsx');

        $factory = $this->mock(ExportFileFactoryInterface::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(ExportTypeEnum::WiperAdapterAudit, null, null, null)
            ->andReturn($adapter);

        $notifier = $this->mock(ExportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyExportCompleted')
            ->once()
            ->with(Mockery::on(function (ExportCompletionNotificationDTO $payload): bool {
                return $payload->exportType === ExportTypeEnum::WiperAdapterAudit
                    && $payload->path === 'exports/warehouse-wiper-adapter-audit.xlsx';
            }));

        app(ExportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-adapter-audit',
            'export_type' => 'wiper_adapter_audit',
        ]);
    }

    /**
     * Проверяет регистрацию Warehouse export событий в rabbit-transport config.
     */
    public function test_warehouse_export_events_are_registered(): void
    {
        $handler = [ExportFileRequestedHandler::class, 'handle'];

        $this->assertSame($handler, config('rabbit-transport.inbound.WAREHOUSE_NOMENCLATURE_EXPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.WAREHOUSE_KIT_EXPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.WAREHOUSE_WIPER_ADAPTER_AUDIT_EXPORT_FILE_REQUESTED'));
        $this->assertSame('warehouse.file.exported', config('rabbit-transport.outbound.WAREHOUSE_FILE_EXPORTED'));

        $bindings = (array) config('rabbit-transport.setup.bindings');

        $this->assertContains('crm.warehouse.nomenclatures.export', $bindings);
        $this->assertContains('crm.warehouse.kits.export', $bindings);
        $this->assertContains('crm.warehouse.wiper-adapter-audit.export', $bindings);
    }
}
