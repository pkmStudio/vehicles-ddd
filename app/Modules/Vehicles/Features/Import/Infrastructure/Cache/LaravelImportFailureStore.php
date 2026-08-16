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
     * Получить накопленные failures из Laravel Cache.
     *
     * Шаги:
     * 1) Прочитать cache entry по ключу import run.
     * 2) Вернуть массив failures или пустой список при некорректном payload.
     *
     * @return array<int, array{
     *     row: int,
     *     attribute: string,
     *     errors: array<int, string>,
     *     values: array<int|string, string|int|float|bool|null>
     * }>
     */
    public function get(string $key): array
    {
        $failures = Cache::get($key, []);

        return is_array($failures) ? $failures : [];
    }

    /**
     * Удалить накопленные failures import run.
     *
     * Шаги:
     * 1) Найти cache entry по ключу.
     * 2) Удалить entry из Laravel Cache.
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }
}
