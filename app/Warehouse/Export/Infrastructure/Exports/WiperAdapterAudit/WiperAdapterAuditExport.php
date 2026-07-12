<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Infrastructure\Exports\WiperAdapterAudit;

use App\Warehouse\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Warehouse\Export\Domain\DTOs\ExportRunContextDTO;
use App\Warehouse\WiperAdapterAudit\Domain\Contracts\Services\WiperAdapterAuditServiceInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Excel-адаптер, который сохраняет отчёт аудита адаптеров дворников Warehouse-наборов.
 */
final readonly class WiperAdapterAuditExport implements FromCollection, WiperAdapterAuditExportInterface, WithHeadings, WithMapping, WithTitle
{
    /**
     * Получает сервис расчёта готовых строк отчёта.
     */
    public function __construct(
        private WiperAdapterAuditServiceInterface $audit,
    ) {}

    /**
     * Сохраняет xlsx-файл отчёта на Storage disk и возвращает путь.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config(
            key: 'warehouse.export.output.disk',
            default: 'local',
        );
        $directory = (string) config(
            key: 'warehouse.export.output.directory',
            default: 'exports',
        );
        $path = sprintf('%s/warehouse-wiper-adapter-audit-%s.xlsx', $directory, $context->runId);

        ExcelFacade::store(
            export: $this,
            filePath: $path,
            diskName: $disk,
            writerType: Excel::XLSX,
        );

        return $path;
    }

    /**
     * Возвращает название листа отчёта.
     */
    public function title(): string
    {
        return 'Адаптеры';
    }

    /**
     * Возвращает готовые строки отчёта для maatwebsite/excel.
     */
    public function collection(): Collection
    {
        return $this->audit->rows();
    }

    /**
     * Мапит одну строку отчёта в плоский массив значений Excel.
     *
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->kitId,
            $row->kit,
            $row->mismatchedAdapters,
            $row->place,
        ];
    }

    /**
     * Возвращает заголовки старого отчёта dan-center.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID Набора',
            'Набор',
            'Несовпадающие адаптеры',
            'Где лежит',
        ];
    }
}
