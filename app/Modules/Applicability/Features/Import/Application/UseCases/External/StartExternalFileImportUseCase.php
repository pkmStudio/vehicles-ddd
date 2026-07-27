<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Application\UseCases\External;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Factories\ImportFileFactoryInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\Contracts\UseCases\External\StartExternalFileImportUseCaseInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportRunContextDTO;
use Throwable;

final readonly class StartExternalFileImportUseCase implements StartExternalFileImportUseCaseInterface
{
    public function __construct(
        private ExternalImportCacheServiceInterface $cache,
        private ImportFileFactoryInterface $importFactory,
    ) {}

    public function execute(ExternalImportFileRequestDTO $request): void
    {
        if (! $this->cache->accept($request->runId)) {
            return;
        }

        try {
            if ($request->cleanupAfterImport) {
                $this->cache->rememberCleanup($request);
            }

            $context = new ImportRunContextDTO(
                userId: $request->userId,
                runId: $request->runId,
            );

            $this->importFactory
                ->make($request->importType)
                ->import($request->path, $context, $request->disk);
        } catch (Throwable $e) {
            $this->cache->forgetAccepted($request->runId);

            throw $e;
        }
    }
}
