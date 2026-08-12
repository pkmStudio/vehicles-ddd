<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Import;

use App\Modules\Applicability\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;
use App\Modules\Applicability\Features\Import\Infrastructure\Messaging\Handlers\ImportFileRequestedHandler;
use Mockery;
use PkmStudio\DanWireContracts\Vehicles\Modules\Applicability\Features\Import\DTO\ImportFileRequested as WireImportFileRequested;
use Tests\TestCase;

final class ImportFileRequestedHandlerTest extends TestCase
{
    public function test_accepts_published_wire_import_request_payload(): void
    {
        config(['filesystems.files_disk' => 's3']);

        $useCase = $this->mock(StartExternalFileImportUseCaseInterface::class);
        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (ExternalImportFileRequestDTO $request): bool {
                return $request->userId === 42
                    && $request->operationId === 'wire-import-applicability'
                    && $request->importType === ImportTypeEnum::KitApplicability
                    && $request->disk === 's3'
                    && $request->path === 'applicability/import.xlsx'
                    && $request->cleanupAfterImport === true;
            }));

        $message = new WireImportFileRequested(
            userId: 42,
            operationId: 'wire-import-applicability',
            importType: 'kit_applicability',
            path: 'applicability/import.xlsx',
        );

        app(ImportFileRequestedHandler::class)->handle($message->toArray());
    }
}
