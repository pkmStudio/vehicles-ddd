<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Services\External;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Services\External\ExternalImportCacheServiceInterface;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileCleanupDTO;
use App\Modules\Applicability\Features\Import\Domain\DTOs\ExternalImportFileRequestDTO;
use Illuminate\Support\Facades\Cache;

final readonly class ExternalImportCacheService implements ExternalImportCacheServiceInterface
{
    public function accept(string $runId): bool
    {
        return Cache::add($this->acceptedKey($runId), true, $this->ttlSeconds());
    }

    public function forgetAccepted(string $runId): void
    {
        Cache::forget($this->acceptedKey($runId));
    }

    public function rememberCleanup(ExternalImportFileRequestDTO $request): void
    {
        Cache::put(
            key: $this->cleanupKey($request->runId),
            value: ['disk' => $request->disk, 'path' => $request->path],
            ttl: $this->ttlSeconds(),
        );
    }

    public function pullCleanup(string $runId): ?ExternalImportFileCleanupDTO
    {
        $key = $this->cleanupKey($runId);
        $cleanup = Cache::get($key);
        Cache::forget($key);

        if (! is_array($cleanup) || ! isset($cleanup['disk'], $cleanup['path'])) {
            return null;
        }

        return new ExternalImportFileCleanupDTO(
            disk: (string) $cleanup['disk'],
            path: (string) $cleanup['path'],
        );
    }

    private function acceptedKey(string $runId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.accepted'), $runId);
    }

    private function cleanupKey(string $runId): string
    {
        return sprintf((string) config('applicability.import.external.cache.keys.cleanup'), $runId);
    }

    private function ttlSeconds(): int
    {
        return (int) config('applicability.import.external.cache.ttl_seconds');
    }
}
