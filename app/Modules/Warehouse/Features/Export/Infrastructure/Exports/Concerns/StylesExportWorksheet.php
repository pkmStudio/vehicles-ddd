<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait StylesExportWorksheet
{
    /**
     * Применяет общий стиль к Excel-листу экспорта.
     *
     * Шаги:
     * 1) Выделить первую строку жирным шрифтом и серой заливкой.
     * 2) Определить последний используемый столбец листа.
     * 3) Включить auto-size для каждой колонки с данными.
     */
    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE6E6E6'],
            ],
        ]);

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }
    }
}
