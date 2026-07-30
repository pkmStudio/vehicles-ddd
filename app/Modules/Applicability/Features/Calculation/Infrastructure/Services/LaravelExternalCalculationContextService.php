<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Services;

use App\Modules\Applicability\Features\Calculation\Domain\Contracts\Services\ExternalCalculationContextServiceInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class LaravelExternalCalculationContextService implements ExternalCalculationContextServiceInterface
{
    public function __construct(
        private CacheRepository $cache,
    ) {}

    public function rememberUserId(string $operationId, int $userId): void
    {
        $this->cache->put($this->userIdKey($operationId), $userId, $this->ttl());
    }

    public function pullUserId(string $operationId): ?int
    {
        $key = $this->userIdKey($operationId);
        $userId = $this->cache->get($key);
        $this->cache->forget($key);

        return is_numeric($userId) ? (int) $userId : null;
    }

    private function userIdKey(string $operationId): string
    {
        return sprintf(
            (string) config('applicability.calculation.external.cache.keys.user_id'),
            $operationId,
        );
    }

    private function ttl(): int
    {
        return max(1, (int) config('applicability.calculation.external.cache.ttl_seconds', 86400));
    }
}
