<?php

declare(strict_types=1);

namespace App\Vehicles\Traits;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Трейт для сохранения ошибок импорта в кэш с блокировкой.
 *
 * Использующий класс обязан объявить свойства:
 *   - string $cacheKey
 *   - string $lockKey
 */
trait CachesImportFailures
{
    private string $cacheKey;

    private string $lockKey;

    /**
     * @throws LockTimeoutException
     */
    public function onFailure(Failure ...$failures): void
    {
        $entries = [];

        foreach ($failures as $failure) {
            $entries[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }

        $cacheKey = $this->cacheKey;
        $lockKey = $this->lockKey;

        Cache::lock($lockKey, seconds: 3)->block(3, function () use ($cacheKey, $entries) {
            $existing = Cache::get($cacheKey, []);
            Cache::put($cacheKey, array_merge($existing, $entries), now()->addMinutes(5));
        });
    }
}
