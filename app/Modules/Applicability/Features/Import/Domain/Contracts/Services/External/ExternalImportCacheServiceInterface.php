<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;

interface ExternalImportCacheServiceInterface
{
    public function accept(string $runId): bool;

    public function forgetAccepted(string $runId): void;

    public function rememberCleanup(ExternalImportFileRequestDTO $request): void;

    public function pullCleanup(string $runId): ?ExternalImportFileCleanupDTO;
}
