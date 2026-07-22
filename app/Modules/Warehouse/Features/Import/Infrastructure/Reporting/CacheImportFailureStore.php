<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Reporting;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel cache-реализация чтения накопленных ошибок Warehouse-импорта.
 */
final readonly class CacheImportFailureStore implements ImportFailureStoreInterface
{
    /**
     * Забирает накопленные failures по cache key и очищает хранилище.
     *
     * @return array<int, mixed>
     */
    public function pull(string $cacheKey): array
    {
        $failures = Cache::get($cacheKey, []);
        Cache::forget($cacheKey);

        return is_array($failures) ? $failures : [];
    }
}
