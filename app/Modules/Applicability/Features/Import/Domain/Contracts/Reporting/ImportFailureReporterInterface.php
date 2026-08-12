<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting;

interface ImportFailureReporterInterface
{
    /**
     * Сохраняет отчет ошибок импорта и возвращает путь к нему.
     *
     * Шаги:
     * 1. Принимает накопленные row failures.
     * 2. Если failures пустые, возвращает `null`.
     * 3. Если failures есть, создает report artifact и возвращает path.
     */
    public function store(array $failures): ?string;
}
