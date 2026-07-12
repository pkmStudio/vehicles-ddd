<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles\Export;

use App\Vehicles\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Vehicles\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Vehicles\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Vehicles\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Vehicles\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Vehicles\Export\Domain\DTOs\ExportRunContextDTO;
use App\Vehicles\Export\Domain\Enums\ExportCompletionStatusEnum;
use App\Vehicles\Export\Domain\Enums\ExportTypeEnum;
use App\Vehicles\Export\Infrastructure\Messaging\Handlers\ExportFileRequestedHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Симметрично ImportFileRequestedHandlerTest — та же проводка (Validator → UseCase →
 * идемпотентность → фабрика → адаптер → уведомление), только для исходящего сценария Export.
 */
final class ExportFileRequestedHandlerTest extends TestCase
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
     * Проверяет happy-path: валидный payload → реальный (не мокнутый) UseCase/идемпотентность
     * → фабрика выбирает нужный тип экспорта → адаптер вызван с нужным контекстом и диском, а
     * по завершении инициатор уведомлён статусом Completed с путём к файлу.
     *
     * Мокает на границе фабрики (ExportFileFactoryInterface), а не конкретного адаптера:
     * Factory::make() резолвит VehicleMultiSheetExportInterface через app()->makeWith() с
     * непустыми параметрами (isAllow) — а Laravel не делает short-circuit на заранее
     * забинженный instance(), когда переданы параметры (needsContextualBuild), так что мок
     * самого адаптера тут был бы тихо проигнорирован контейнером.
     *
     * Шаги:
     * 1. Мокает FileExportInterface (сам адаптер) — ожидает export() с
     *    ExportRunContextDTO(userId: 42, runId: 'run-123') и диском 'local' (свой конфиг,
     *    не из payload), возвращает путь к «сгенерированному» файлу.
     * 2. Мокает ExportFileFactoryInterface::make — ожидает вызов с (Vehicle, isAllow: false)
     *    и возвращает мок адаптера из шага 1.
     * 3. Мокает ExportNotificationServiceInterface — ожидает notifyExportCompleted() со
     *    статусом Completed, тем же runId/exportType и этим же путём.
     * 4. Зовёт handle() с валидным payload (export_type=vehicle_multi_sheet).
     */
    public function test_starts_vehicle_export_and_notifies_completion(): void
    {
        $adapter = Mockery::mock(FileExportInterface::class);
        $adapter->shouldReceive('export')
            ->once()
            ->with(
                Mockery::on(fn (ExportRunContextDTO $context): bool => $context->userId === 42 && $context->runId === 'run-123'),
                'local',
            )
            ->andReturn('exports/vehicle-catalog-run-123.xlsx');

        $factory = $this->mock(ExportFileFactoryInterface::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(ExportTypeEnum::Vehicle, false)
            ->andReturn($adapter);

        $notifier = $this->mock(ExportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyExportCompleted')
            ->once()
            ->with(Mockery::on(function (ExportCompletionNotificationDTO $payload): bool {
                return $payload->userId === 42
                    && $payload->status === ExportCompletionStatusEnum::Completed
                    && $payload->runId === 'run-123'
                    && $payload->exportType === ExportTypeEnum::Vehicle
                    && $payload->path === 'exports/vehicle-catalog-run-123.xlsx';
            }));

        app(ExportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-123',
            'export_type' => 'vehicle_multi_sheet',
        ]);
    }

    /**
     * Проверяет, что невалидный payload (нет обязательного поля export_type) не доходит до
     * UseCase — ошибка валидации логируется, а не бросается исключением.
     *
     * Шаги:
     * 1. Мокает StartExportUseCaseInterface — ожидает, что execute() НЕ вызовется.
     * 2. Ожидает Log::error с указанием invalid_keys, включающим 'export_type'.
     * 3. Зовёт handle() с payload без поля 'export_type'.
     */
    public function test_invalid_payload_is_logged_and_skipped(): void
    {
        $useCase = $this->mock(StartExportUseCaseInterface::class);
        $useCase->shouldNotReceive('execute');

        Log::shouldReceive('error')
            ->once()
            ->with(
                'RabbitMQ: Export file request payload validation failed',
                Mockery::on(fn (array $context): bool => in_array('export_type', $context['invalid_keys'] ?? [], true)),
            );

        app(ExportFileRequestedHandler::class)->handle([
            'user_id' => 42,
            'run_id' => 'run-123',
        ]);
    }

    /**
     * Проверяет идемпотентность по runId: повторная доставка одного и того же сообщения
     * (например, ретрай брокера) не запускает экспорт дважды.
     *
     * Шаги:
     * 1. Мокает FileExportInterface — ожидает export() ровно один раз (см. пояснение о
     *    границе мока в предыдущем тесте).
     * 2. Мокает ExportFileFactoryInterface::make — возвращает этот адаптер.
     * 3. Мокает ExportNotificationServiceInterface — ожидает notifyExportCompleted() ровно
     *    один раз.
     * 4. Дважды зовёт handle() с одинаковым payload (тот же run_id).
     * 5. Mockery сам провалит тест, если export()/notifyExportCompleted() вызовутся дважды.
     */
    public function test_duplicate_run_id_is_skipped(): void
    {
        $adapter = Mockery::mock(FileExportInterface::class);
        $adapter->shouldReceive('export')->once()->andReturn('exports/vehicle-catalog-run-dup.xlsx');

        $factory = $this->mock(ExportFileFactoryInterface::class);
        $factory->shouldReceive('make')->once()->andReturn($adapter);

        $notifier = $this->mock(ExportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyExportCompleted')->once();

        $payload = [
            'user_id' => 42,
            'run_id' => 'run-dup',
            'export_type' => 'vehicle_multi_sheet',
        ];

        app(ExportFileRequestedHandler::class)->handle($payload);
        app(ExportFileRequestedHandler::class)->handle($payload);
    }

    /**
     * Проверяет саму проводку config/rabbit-transport.php: оба inbound-имени событий экспорта
     * забинжены на один и тот же Handler, конкретный тип выбирается внутри по export_type.
     *
     * Шаги:
     * 1. Проверяет, что VEHICLES_EXPORT_FILE_REQUESTED/ENGINES_EXPORT_FILE_REQUESTED указывают
     *    на [ExportFileRequestedHandler, 'handle'].
     * 2. Проверяет, что routing-key bindings в setup.bindings содержат оба типа экспорта.
     */
    public function test_export_file_events_have_unique_names_and_same_handler(): void
    {
        $handler = [ExportFileRequestedHandler::class, 'handle'];

        $this->assertSame($handler, config('rabbit-transport.inbound.VEHICLES_EXPORT_FILE_REQUESTED'));
        $this->assertSame($handler, config('rabbit-transport.inbound.ENGINES_EXPORT_FILE_REQUESTED'));

        $bindings = (array) config('rabbit-transport.setup.bindings');

        $this->assertContains('crm.vehicles.export', $bindings);
        $this->assertContains('crm.engines.export', $bindings);
    }
}
