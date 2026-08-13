<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\ImportFailureStoreInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

/**
 * Laravel cache-реализация чтения накопленных ошибок импорта применяемости.
 */
final readonly class CacheImportFailureStore implements ImportFailureStoreInterface
{
    /**
     * Получает Laravel cache factory для чтения failures import-а.
     *
     * Шаги:
     * 1. Сохраняет cache factory, чтобы использовать default store.
     * 2. Оставляет очистку конкретного key в методе `pull()`.
     */
    public function __construct(
        private CacheFactory $cache,
    ) {}

    /**
     * Забирает накопленные failures по cache key и очищает хранилище.
     *
     * Шаги:
     * 1. Читает массив failures по cache key.
     * 2. Удаляет cache key, чтобы отчет не был обработан повторно.
     * 3. Возвращает пустой массив, если сохраненное значение повреждено или отсутствует.
     */
    public function pull(string $cacheKey): array
    {
        $failures = $this->cache->store()->get($cacheKey, []);
        $this->cache->store()->forget($cacheKey);

        return is_array($failures) ? $failures : [];
    }
}
