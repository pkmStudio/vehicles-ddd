<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Reporting;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Laravel Excel export adapter для отчета об ошибках import rows.
 */
final readonly class FailuresExport implements FailuresExportInterface, FromCollection, WithHeadings
{
    /**
     * Получить failures, накопленные import flow.
     *
     * Шаги:
     * 1) Принять failure payloads из reporter service.
     * 2) Сохранить их как immutable state export adapter-а.
     *
     * @param  array<int, array{
     *     row: int,
     *     attribute: string,
     *     errors: array<int, string>,
     *     values: array<int|string, string|int|float|bool|null>
     * }>  $failures
     */
    public function __construct(
        private array $failures,
    ) {}

    /**
     * Вернуть headings отчета об ошибках импорта.
     *
     * Шаги:
     * 1) Собрать фиксированные колонки failure report.
     * 2) Вернуть их в порядке Excel-листа.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Row',
            'Attribute',
            'Error',
            'Value',
        ];
    }

    /**
     * Преобразовать failures в строки Excel report.
     *
     * Шаги:
     * 1) Обойти накопленные validation failures.
     * 2) Нормализовать errors/value payload в строковые значения.
     * 3) Вернуть Laravel collection строк отчета.
     *
     * @return Collection<int, array{row: int, attribute: string, error: string, value: string|false}>
     */
    public function collection(): Collection
    {
        $toFailureRow = function (array $failure): array {
            return [
                'row' => $failure['row'],
                'attribute' => $failure['attribute'],
                'error' => implode('; ', $failure['errors']),
                'value' => json_encode($failure['values'], JSON_UNESCAPED_UNICODE),
            ];
        };

        return collect($this->failures)->map($toFailureRow);
    }
}
