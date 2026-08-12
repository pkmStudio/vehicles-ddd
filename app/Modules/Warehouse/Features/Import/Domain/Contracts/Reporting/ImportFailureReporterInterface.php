<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting;

use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;

/**
 * Порт сохранения отчёта об ошибках Warehouse-импорта в файл.
 */
interface ImportFailureReporterInterface
{
    /**
     * Сохраняет накопленные ошибки импорта в CSV и возвращает путь к файлу, либо null, если
     * ошибок не было. $type попадает в имя файла — чтобы по одному только пути было видно,
     * из какого импорта (Nomenclature/PackDimension/Kit) отчёт.
     *
     * Шаги:
     * 1) Принять накопленные validation failures и тип импорта.
     * 2) Завершить без файла, если ошибок нет.
     * 3) Сформировать путь отчёта по типу импорта и времени.
     * 4) Сохранить CSV/Excel-отчёт и вернуть путь.
     *
     * @param  array<int, array{row: int, attribute: string, errors: array<int, string>, values: mixed}>  $failures
     */
    public function store(array $failures, ImportTypeEnum $type): ?string;
}
