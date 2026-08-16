<?php

declare(strict_types=1);

namespace Tests\Feature\Applicability\Import;

use App\Modules\Applicability\Features\Import\Application\UseCases\External\StartExternalFileImportUseCase;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Imports\FileImportInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportRunContextDTO;
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

        $cache = Mockery::mock(ExternalImportCacheServiceInterface::class);
        $cache->shouldReceive('accept')
            ->once()
            ->with('wire-import-applicability')
            ->andReturnTrue();
        $cache->shouldReceive('rememberCleanup')
            ->once()
            ->with(Mockery::on(fn (ExternalImportFileRequestDTO $request): bool => $request->userId === 42
                && $request->operationId === 'wire-import-applicability'
                && $request->importType === ImportTypeEnum::KitApplicability
                && $request->disk === 's3'
                && $request->path === 'applicability/import.xlsx'
                && $request->cleanupAfterImport === true));

        $import = Mockery::mock(FileImportInterface::class);
        $import->shouldReceive('import')
            ->once()
            ->with(
                'applicability/import.xlsx',
                Mockery::on(fn (ImportRunContextDTO $context): bool => $context->userId === 42
                    && $context->operationId === 'wire-import-applicability'),
                's3',
            );

        $factory = Mockery::mock(ImportFileFactoryInterface::class);
        $factory->shouldReceive('make')
            ->once()
            ->with(ImportTypeEnum::KitApplicability)
            ->andReturn($import);

        $this->app->instance(
            StartExternalFileImportUseCase::class,
            new StartExternalFileImportUseCase($cache, $factory),
        );

        $message = new WireImportFileRequested(
            userId: 42,
            operationId: 'wire-import-applicability',
            importType: 'kit_applicability',
            path: 'applicability/import.xlsx',
        );

        app(ImportFileRequestedHandler::class)->handle($message->toArray());
    }
}
