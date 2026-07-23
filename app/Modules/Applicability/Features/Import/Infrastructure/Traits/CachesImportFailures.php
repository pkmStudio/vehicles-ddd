<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Traits;

use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Validators\Failure;

trait CachesImportFailures
{
    private string $cacheKey;

    private string $lockKey;

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

        Cache::lock($lockKey, 3)->block(3, function () use ($cacheKey, $entries): void {
            $existing = Cache::get($cacheKey, []);

            Cache::put(
                key: $cacheKey,
                value: array_merge(is_array($existing) ? $existing : [], $entries),
                ttl: now()->addMinutes(5),
            );
        });
    }
}
