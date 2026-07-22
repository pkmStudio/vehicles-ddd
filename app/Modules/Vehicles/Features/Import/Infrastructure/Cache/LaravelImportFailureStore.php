<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Cache;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache-адаптер накопленных ошибок импорта.
 */
final readonly class LaravelImportFailureStore implements ImportFailureStoreInterface
{
    /**
     * @return array<int, mixed>
     */
    public function get(string $key): array
    {
        $failures = Cache::get($key, []);

        return is_array($failures) ? $failures : [];
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
