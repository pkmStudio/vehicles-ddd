<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Imports;

use App\Modules\Applicability\Features\Import\Domain\DTOs\ImportRunContextDTO;

interface FileImportInterface
{
    public function import(string $path, ImportRunContextDTO $context, ?string $disk = null): void;
}
