<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

/**
 * Laravel cache-реализация чтения накопленных ошибок импорта применяемости.
 */
final readonly class CacheImportFailureStore implements ImportFailureStoreInterface
{
    public function __construct(
        private CacheFactory $cache,
    ) {}

    /**
     * Забирает накопленные failures по cache key и очищает хранилище.
     */
    public function pull(string $cacheKey): array
    {
        $failures = $this->cache->store()->get($cacheKey, []);
        $this->cache->store()->forget($cacheKey);

        return is_array($failures) ? $failures : [];
    }
}
