<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting;

interface ImportFailureReporterInterface
{
    /**
     * Сохраняет отчёт об ошибках импорта в общее хранилище.
     *
     * Шаги:
     * 1) Проверить наличие failures.
     * 2) Сформировать файл отчета, если ошибки есть.
     * 3) Вернуть путь к файлу или null.
     *
     * @param  array<int, mixed>  $failures
     * @return string|null путь к сохранённому файлу или null, если ошибок нет
     */
    public function store(array $failures): ?string;
}
