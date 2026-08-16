<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting;

/**
 * Хранилище накопленных построчных ошибок импорта.
 */
interface ImportFailureStoreInterface
{
    /**
     * Возвращает накопленные ошибки по cache-ключу.
     *
     * Шаги:
     * 1) Прочитать failure payload из cache.
     * 2) Вернуть список failures или пустой массив.
     *
     * @return array<int, array{
     *     row: int,
     *     attribute: string,
     *     errors: array<int, string>,
     *     values: array<int|string, string|int|float|bool|null>
     * }>
     */
    public function get(string $key): array;

    /**
     * Очищает накопленные ошибки по cache-ключу.
     *
     * Шаги:
     * 1) Найти cache entry по ключу.
     * 2) Удалить накопленные failures.
     */
    public function forget(string $key): void;
}
