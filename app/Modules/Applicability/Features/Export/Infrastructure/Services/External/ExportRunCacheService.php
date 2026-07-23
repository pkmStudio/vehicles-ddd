<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Services\External;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\External\ExportRunCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

final readonly class ExportRunCacheService implements ExportRunCacheServiceInterface
{
    public function accept(string $runId): bool
    {
        return Cache::add($this->acceptedKey($runId), true, now()->addSeconds($this->ttlSeconds()));
    }

    public function forgetAccepted(string $runId): void
    {
        Cache::forget($this->acceptedKey($runId));
    }

    private function acceptedKey(string $runId): string
    {
        return sprintf((string) config('applicability.export.external.cache.keys.accepted'), $runId);
    }

    private function ttlSeconds(): int
    {
        return (int) config('applicability.export.external.cache.ttl_seconds');
    }
}
