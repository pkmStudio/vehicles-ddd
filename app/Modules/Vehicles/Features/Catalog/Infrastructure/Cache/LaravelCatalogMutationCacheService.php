<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Catalog\Infrastructure\Cache;

use App\Modules\Vehicles\Features\Catalog\Domain\Contracts\Services\CatalogMutationCacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache-адаптер идемпотентности входящих мутаций каталога.
 */
final readonly class LaravelCatalogMutationCacheService implements CatalogMutationCacheServiceInterface
{
    /**
     * Атомарно принимает operationId для идемпотентной обработки.
     *
     * Шаги:
     * - Собрать ключ идемпотентности для операции.
     * - Попытаться атомарно записать ключ в Laravel Cache с настроенным сроком жизни.
     * - Вернуть false, если такой operationId уже был принят ранее.
     */
    public function accept(string $operationId): bool
    {
        return Cache::add($this->key($operationId), true, $this->ttlSeconds());
    }

    /**
     * Снимает отметку принятого operationId после сбоя обработки.
     *
     * Шаги:
     * - Собрать ключ идемпотентности для операции.
     * - Удалить ключ из Laravel Cache, чтобы повторная обработка была возможна.
     */
    public function forgetAccepted(string $operationId): void
    {
        Cache::forget($this->key($operationId));
    }

    /**
     * Собирает ключ кэша идемпотентности по operationId.
     *
     * Шаги:
     * - Прочитать шаблон ключа из конфигурации каталога.
     * - Подставить operationId в шаблон.
     */
    private function key(string $operationId): string
    {
        return sprintf(
            (string) config('vehicles-catalog.mutations.cache.keys.accepted'),
            $operationId,
        );
    }

    /**
     * Возвращает срок жизни ключа идемпотентности в секундах.
     *
     * Шаги:
     * - Прочитать настройку ttl_seconds из конфигурации каталога.
     * - Защититься от нулевого или отрицательного значения.
     */
    private function ttlSeconds(): int
    {
        return max(1, (int) config('vehicles-catalog.mutations.cache.ttl_seconds', 86400));
    }
}
