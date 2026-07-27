<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Import\Infrastructure\Reporting;

use App\Modules\Warehouse\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use App\Modules\Warehouse\Features\Import\Domain\Enums\ImportTypeEnum;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Рендерит накопленные ошибки Warehouse-импорта в плоский CSV-лист.
 */
final readonly class FailuresExport implements FailuresExportInterface, FromCollection, WithHeadings
{
    /**
     * Позиция колонки «Тип товара» в сырой строке Nomenclature-импорта (см.
     * ImportNomenclatureFromRowService) — нужна только чтобы вытащить читаемую категорию в
     * отдельную колонку отчёта, к парсингу самой строки импорта не относится.
     */
    private const int NOMENCLATURE_TYPE_NAME_INDEX = 1;

    /**
     * Позиция колонки «Тип товара» в сырой строке PackDimension-импорта.
     */
    private const int PACK_DIMENSION_TYPE_NAME_INDEX = 7;

    /**
     * @param  array<int, array{row: int, attribute: string, errors: array<int, string>, values: mixed}>  $failures
     */
    public function __construct(
        private array $failures,
        private ImportTypeEnum $type,
    ) {}

    /**
     * Возвращает строки отчёта об ошибках импорта.
     */
    public function collection(): Collection
    {
        $toFailureRow = fn (array $failure): array => [
            $failure['row'],
            $this->category($failure['values']),
            $failure['attribute'],
            implode('; ', $failure['errors']),
            json_encode($failure['values'], JSON_UNESCAPED_UNICODE),
        ];

        return collect($this->failures)->map($toFailureRow);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Строка', 'Категория', 'Поле', 'Ошибка', 'Значение'];
    }

    /**
     * Достаёт читаемое название категории (тип товара) из сырой строки — только для
     * Nomenclature-импорта, у остальных типов позиции колонок другие, поэтому там просто пусто.
     */
    private function category(mixed $values): string
    {
        if (! is_array($values)) {
            return '';
        }

        return match ($this->type) {
            ImportTypeEnum::Nomenclature => (string) ($values[self::NOMENCLATURE_TYPE_NAME_INDEX] ?? ''),
            ImportTypeEnum::PackDimension => (string) ($values[self::PACK_DIMENSION_TYPE_NAME_INDEX] ?? ''),
            ImportTypeEnum::Kit => '',
        };
    }
}
