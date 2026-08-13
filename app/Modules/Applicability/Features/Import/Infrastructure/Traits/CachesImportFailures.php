<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Traits;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Копит ошибки построчного импорта в cache под блокировкой.
 */
trait CachesImportFailures
{
    private string $cacheKey;

    private string $lockKey;

    /**
     * Возвращает cache factory класса-хоста.
     *
     * Шаги:
     * 1. Делегирует выбор cache factory concrete import adapter-у.
     * 2. Позволяет trait-у не знать, как adapter восстанавливает зависимости после queue serialization.
     */
    abstract protected function cache(): CacheFactory;

    /**
     * Сохраняет ошибки строки в cache под блокировкой.
     *
     * Шаги:
     * 1. Преобразует каждый Failure в плоский массив для будущего CSV-отчета.
     * 2. Под lock читает уже накопленные записи текущего import run.
     * 3. Дописывает новые entries и продлевает TTL transient cache.
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

        $this->cache()->store()->lock($lockKey, 3)->block(3, function () use ($cacheKey, $entries): void {
            $existing = $this->cache()->store()->get($cacheKey, []);

            $this->cache()->store()->put(
                key: $cacheKey,
                value: array_merge(is_array($existing) ? $existing : [], $entries),
                ttl: now()->addMinutes(5),
            );
        });
    }
}
