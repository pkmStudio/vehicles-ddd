<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\DTOs;

use App\Modules\Applicability\Features\Import\Domain\Enums\ImportTypeEnum;

final readonly class ExternalImportFileRequestDTO
{
    public function __construct(
        public int $userId,
        public string $operationId,
        public ImportTypeEnum $importType,
        public string $disk,
        public string $path,
        public bool $cleanupAfterImport = true,
    ) {}
}
