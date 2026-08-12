<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Features\Export\Infrastructure\Exports\WiperAdapterAudit;

use App\Modules\Warehouse\Features\Export\Domain\Contracts\Clients\WiperAdapterAuditClientInterface;
use App\Modules\Warehouse\Features\Export\Domain\Contracts\Exports\WiperAdapterAuditExportInterface;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Warehouse\Features\Export\Domain\DTOs\WiperAdapterAudit\WiperAdapterAuditExportRowDTO;
use App\Modules\Warehouse\Features\Export\Infrastructure\Exports\Concerns\StylesExportWorksheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Excel-адаптер, который сохраняет отчёт аудита адаптеров дворников Warehouse-наборов.
 */
final readonly class WiperAdapterAuditExport implements FromCollection, WiperAdapterAuditExportInterface, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use StylesExportWorksheet;

    /**
     * Получает сервис расчёта готовых строк отчёта.
     * Шаги:
     * 1) Сохранить client port, который отдаёт audit rows.
     * 2) Оставить Excel adapter ответственным только за сохранение и mapping.
     */
    public function __construct(
        private WiperAdapterAuditClientInterface $audit,
    ) {}

    /**
     * Сохраняет xlsx-файл отчёта на Storage disk и возвращает путь.
     * Шаги:
     * 1) Взять disk из аргумента или warehouse.export config.
     * 2) Взять output directory из config.
     * 3) Собрать имя файла с operationId export run.
     * 4) Сохранить текущий export adapter через Laravel Excel в XLSX.
     * 5) Вернуть Storage path сохраненного файла.
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
        $path = sprintf('%s/warehouse-wiper-adapter-audit-%s.xlsx', $directory, $context->operationId);

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
     * Шаги:
     * 1) Вернуть фиксированное имя листа "Адаптеры".
     */
    public function title(): string
    {
        return 'Адаптеры';
    }

    /**
     * Возвращает готовые строки отчёта для maatwebsite/excel.
     * Шаги:
     * 1) Запросить audit rows через WiperAdapterAuditClientInterface.
     * 2) Вернуть collection DTO без дополнительного SQL в export adapter-е.
     *
     * @return Collection<int, WiperAdapterAuditExportRowDTO>
     */
    public function collection(): Collection
    {
        return $this->audit->rows();
    }

    /**
     * Мапит одну строку отчёта в плоский массив значений Excel.
     * Шаги:
     * 1) Взять id набора.
     * 2) Добавить строку состава набора.
     * 3) Добавить список несовпадающих адаптеров.
     * 4) Добавить диагностический текст расположения/количества.
     *
     * @param  WiperAdapterAuditExportRowDTO  $row
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
     * Шаги:
     * 1) Вернуть фиксированный порядок колонок, совместимый с legacy отчетом.
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
