<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Storage;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Storage\ExternalImportFileStorageInterface;
use Illuminate\Support\Facades\Storage;

final readonly class LaravelExternalImportFileStorage implements ExternalImportFileStorageInterface
{
    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }
}
