<?php

declare(strict_types=1);

namespace App\Warehouse\Import\Infrastructure\Reporting;

use App\Warehouse\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Рендерит накопленные ошибки Warehouse-импорта в плоский CSV-лист.
 */
final readonly class FailuresExport implements FailuresExportInterface, FromCollection, WithHeadings
{
    /**
     * @param  array<int, array{row: int, attribute: string, errors: array<int, string>, values: mixed}>  $failures
     */
    public function __construct(
        private array $failures,
    ) {}

    /**
     * Возвращает строки отчёта об ошибках импорта.
     */
    public function collection(): Collection
    {
        return collect($this->failures)->map(fn (array $failure): array => [
            $failure['row'],
            $failure['attribute'],
            implode('; ', $failure['errors']),
            json_encode($failure['values'], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Row', 'Attribute', 'Error', 'Value'];
    }
}
