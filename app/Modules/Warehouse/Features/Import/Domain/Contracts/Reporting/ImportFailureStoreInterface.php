<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting;

/**
 * Порт чтения и очистки накопленных ошибок Warehouse-импорта.
 */
interface ImportFailureStoreInterface
{
    /**
     * Забирает накопленные failures по cache key и очищает хранилище.
     *
     * @return array<int, mixed>
     */
    public function pull(string $cacheKey): array;
}
