<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Storage;

interface ExternalImportFileStorageInterface
{
    public function delete(string $disk, string $path): void;
}
