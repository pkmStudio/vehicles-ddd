<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Calculation\Infrastructure\Reporting;

use App\Modules\Applicability\Features\Calculation\Domain\DTOs\Calculation\KitApplicabilityCalculationResultDTO;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final readonly class CalculationFailuresExport implements FromCollection, WithHeadings
{
    /**
     * Получает aggregate result с ошибками расчета.
     *
     * Шаги:
     * 1. Сохраняет result текущего operation.
     * 2. Оставляет построение строк и заголовков Excel export-методам.
     */
    public function __construct(
        private KitApplicabilityCalculationResultDTO $result,
    ) {}

    /**
     * Формирует строки CSV-отчета по ошибкам расчета.
     *
     * Шаги:
     * 1. Обходит errors из aggregate result.
     * 2. Добавляет operation id, порядковый номер и текст ошибки.
     * 3. Возвращает Laravel collection строк для Excel writer.
     */
    public function collection(): Collection
    {
        return collect($this->result->errors)->map(fn (string $error, int $index): array => [
            $this->result->operationId,
            $index + 1,
            $error,
        ]);
    }

    /**
     * Возвращает заголовки CSV-отчета по ошибкам расчета.
     *
     * Шаги:
     * 1. Фиксирует колонку run id.
     * 2. Фиксирует порядковый номер ошибки.
     * 3. Фиксирует текст ошибки.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Run ID', 'Number', 'Error'];
    }
}
