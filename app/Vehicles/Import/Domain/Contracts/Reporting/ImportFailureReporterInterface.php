<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Domain\Contracts\Reporting;

interface ImportFailureReporterInterface
{
    /**
     * Сохраняет отчёт об ошибках импорта в общее хранилище.
     *
     * @param  array<int, mixed>  $failures
     * @return string|null путь к сохранённому файлу или null, если ошибок нет
     */
    public function store(array $failures): ?string;
}
