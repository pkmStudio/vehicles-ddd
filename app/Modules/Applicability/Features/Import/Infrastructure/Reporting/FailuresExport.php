<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Import\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Import\Domain\Contracts\Reporting\FailuresExportInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final readonly class FailuresExport implements FailuresExportInterface, FromCollection, WithHeadings
{
    /**
     * Получает строки ошибок, которые нужно записать в CSV-отчет.
     *
     * Шаги:
     * 1. Сохраняет failures, собранные Laravel Excel import adapter-ом.
     * 2. Оставляет преобразование в плоские строки методу `collection()`.
     */
    public function __construct(
        private array $failures,
    ) {}

    /**
     * Преобразует failures в строки CSV-отчета.
     *
     * Шаги:
     * 1. Обходит каждую сохраненную ошибку строки.
     * 2. Выводит номер строки, атрибут, текст ошибок и исходные значения.
     * 3. Кодирует исходные значения JSON-ом без потери кириллицы.
     */
    public function collection(): Collection
    {
        return collect($this->failures)->map(static fn (array $failure): array => [
            $failure['row'],
            $failure['attribute'],
            implode('; ', $failure['errors']),
            json_encode($failure['values'], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Возвращает заголовки CSV-отчета ошибок импорта.
     *
     * Шаги:
     * 1. Фиксирует технические колонки Laravel Excel failure.
     * 2. Возвращает порядок, которому соответствует `collection()`.
     */
    public function headings(): array
    {
        return ['Row', 'Attribute', 'Error', 'Value'];
    }
}
