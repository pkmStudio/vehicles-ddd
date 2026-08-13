<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting;

interface ImportFailureStoreInterface
{
    /**
     * Забирает накопленные ошибки строк import-а из transient store.
     *
     * Шаги:
     * 1. Читает failures по cache key конкретного import run.
     * 2. Возвращает массив ошибок для report listener-а.
     */
    public function pull(string $cacheKey): array;
}
