<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use Illuminate\Support\Facades\Cache;

final readonly class CacheImportFailureStore implements ImportFailureStoreInterface
{
    public function pull(string $cacheKey): array
    {
        $failures = Cache::get($cacheKey, []);
        Cache::forget($cacheKey);

        return is_array($failures) ? $failures : [];
    }
}
