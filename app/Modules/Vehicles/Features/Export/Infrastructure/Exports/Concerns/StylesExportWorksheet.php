<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Export\Infrastructure\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Общий styling concern для Laravel Excel worksheets Vehicles Export.
 */
trait StylesExportWorksheet
{
    /**
     * Применяет базовое оформление worksheet.
     *
     * Шаги:
     * 1) Сделать первую строку жирной и залить серым фоном.
     * 2) Определить индекс последней колонки.
     * 3) Включить autosize для каждой колонки worksheet.
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
