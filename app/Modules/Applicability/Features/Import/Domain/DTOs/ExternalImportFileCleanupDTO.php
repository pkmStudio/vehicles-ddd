<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\DTOs;

final readonly class ExternalImportFileCleanupDTO
{
    public function __construct(
        public string $disk,
        public string $path,
    ) {}
}
