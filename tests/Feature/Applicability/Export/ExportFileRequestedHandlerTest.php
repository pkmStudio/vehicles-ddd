<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Export;

use App\Modules\Applicability\Features\Export\Application\UseCases\External\StartExportUseCase;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\FileExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Factories\ExportFileFactoryInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Notifications\ExportNotificationServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportCompletionNotificationDTO;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Handlers\ExportFileRequestedHandler;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Export\DTO\ExportFileRequested as WireExportFileRequested;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Export\Enums\ApplicabilityExportType;
use Tests\TestCase;

final class ExportFileRequestedHandlerTest extends TestCase
{
    public function test_accepts_published_wire_vehicle_export_request_payload(): void
    {
        $this->handleExportRequest(
            wireExportType: ApplicabilityExportType::VehicleKitApplicability,
            localExportType: ExportTypeEnum::VehicleKitApplicability,
        );
    }

    public function test_accepts_published_wire_modification_export_request_payload(): void
    {
        $this->handleExportRequest(
            wireExportType: ApplicabilityExportType::ModificationKitApplicability,
            localExportType: ExportTypeEnum::ModificationKitApplicability,
        );
    }

    private function handleExportRequest(
        ApplicabilityExportType $wireExportType,
        ExportTypeEnum $localExportType,
    ): void {
        config(['applicability.export.output.disk' => 's3']);

        $operationId = 'wire-export-'.$wireExportType->value;
        $cache = Mockery::mock(ExportRunCacheServiceInterface::class);
        $cache->shouldReceive('accept')
            ->once()
            ->with($operationId)
            ->andReturnTrue();

        $export = Mockery::mock(FileExportInterface::class);
        $export->shouldReceive('export')
            ->once()
            ->with(
                Mockery::on(fn (ExportRunContextDTO $context): bool => $context->userId === 42
                    && $context->operationId === $operationId),
                's3',
            )
            ->andReturn('exports/applicability.xlsx');

        $factory = Mockery::mock(ExportFileFactoryInterface::class);
        $factory->shouldReceive('make')
            ->once()
            ->with($localExportType)
            ->andReturn($export);

        $notifier = Mockery::mock(ExportNotificationServiceInterface::class);
        $notifier->shouldReceive('notifyExportCompleted')
            ->once()
            ->with(Mockery::on(fn (ExportCompletionNotificationDTO $notification): bool => $notification->userId === 42
                && $notification->operationId === $operationId
                && $notification->exportType === $localExportType
                && $notification->disk === 's3'
                && $notification->path === 'exports/applicability.xlsx'));

        $this->app->instance(
            StartExportUseCase::class,
            new StartExportUseCase($cache, $factory, $notifier),
        );

        $message = new WireExportFileRequested(
            userId: 42,
            operationId: $operationId,
            exportType: $wireExportType,
        );

        app(ExportFileRequestedHandler::class)->handle($message->toArray());
    }
}
