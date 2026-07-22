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
     * @return array<int, mixed>
     */
    public function get(string $key): array;

    /**
     * Очищает накопленные ошибки по cache-ключу.
     */
    public function forget(string $key): void;
}
