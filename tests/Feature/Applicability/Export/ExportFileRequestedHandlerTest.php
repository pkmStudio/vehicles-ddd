<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Export;

use App\Modules\Applicability\Features\Export\Domain\Contracts\UseCases\External\StartExportUseCaseInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportFileRequestDTO;
use App\Modules\Applicability\Features\Export\Domain\Enums\ExportTypeEnum;
use App\Modules\Applicability\Features\Export\Infrastructure\Messaging\Handlers\ExportFileRequestedHandler;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Export\DTO\ExportFileRequested as WireExportFileRequested;
use Tests\TestCase;

final class ExportFileRequestedHandlerTest extends TestCase
{
    public function test_accepts_published_wire_export_request_payload(): void
    {
        config(['applicability.export.output.disk' => 's3']);

        $useCase = $this->mock(StartExportUseCaseInterface::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (ExportFileRequestDTO $request): bool {
                return $request->userId === 42
                    && $request->operationId === 'wire-export-applicability'
                    && $request->exportType === ExportTypeEnum::VehicleKitApplicability
                    && $request->disk === 's3';
            }));

        $message = new WireExportFileRequested(
            userId: 42,
            operationId: 'wire-export-applicability',
            exportType: 'vehicle_kit_applicability',
        );

        app(ExportFileRequestedHandler::class)->handle($message->toArray());
    }
}
