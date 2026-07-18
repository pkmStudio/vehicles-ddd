<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting;

/**
 * Порт сохранения отчёта об ошибках Warehouse-импорта в файл.
 */
interface ImportFailureReporterInterface
{
    /**
     * Сохраняет накопленные ошибки импорта в CSV и возвращает путь к файлу, либо null, если
     * ошибок не было.
     *
     * @param  array<int, array{row: int, attribute: string, errors: array<int, string>, values: mixed}>  $failures
     */
    public function store(array $failures): ?string;
}
