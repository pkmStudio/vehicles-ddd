<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Traits;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Копит ошибки построчного импорта в cache под блокировкой — та же логика, что у
 * `Vehicles\Import\Infrastructure\Traits\CachesImportFailures` и dan-center
 * `App\Traits\Warehouse\CachesImportFailures`. Использующий класс обязан объявить свойства
 * `string $cacheKey` и `string $lockKey`.
 */
trait CachesImportFailures
{
    private string $cacheKey;

    private string $lockKey;

    /**
     * Этот метод сохраняет ошибки строки в cache под блокировкой.
     *
     * Шаги:
     * 1) Преобразовать каждый Failure в плоский массив {row, attribute, errors, values}.
     * 2) Под Cache::lock прочитать уже накопленные записи и дописать новые.
     * 3) Продлить TTL накопленного списка на 5 минут.
     *
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

        Cache::lock(
            name: $lockKey,
            seconds: 3,
        )->block(
            seconds: 3,
            callback: function () use ($cacheKey, $entries): void {
                $existing = Cache::get(
                    key: $cacheKey,
                    default: [],
                );

                Cache::put(
                    key: $cacheKey,
                    value: array_merge($existing, $entries),
                    ttl: now()->addMinutes(5),
                );
            },
        );
    }
}
