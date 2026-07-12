<?php

declare(strict_types=1);

namespace App\Warehouse\Export\Infrastructure\Exports\Nomenclature;

use App\Warehouse\Export\Domain\Contracts\Exports\NomenclatureByTypeExportInterface;
use App\Warehouse\Export\Domain\DTOs\ExportRunContextDTO;
use App\Warehouse\Export\Infrastructure\Exports\Nomenclature\Sheets\NomenclatureDataSheetExport;
use App\Warehouse\Export\Infrastructure\Exports\Nomenclature\Sheets\NomenclatureReferenceSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

/**
 * Multi-sheet Excel-адаптер для выгрузки Warehouse-номенклатуры выбранного типа.
 */
final readonly class NomenclatureByTypeExport implements NomenclatureByTypeExportInterface, WithMultipleSheets
{
    /**
     * Получает идентификатор типа номенклатуры, который нужен обоим листам.
     */
    public function __construct(
        private int $typeId,
    ) {}

    /**
     * Сохраняет xlsx-файл номенклатуры на Storage disk и возвращает путь.
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
        $path = sprintf('%s/warehouse-nomenclature-type-%d-%s.xlsx', $directory, $this->typeId, $context->runId);

        ExcelFacade::store(
            export: $this,
            filePath: $path,
            diskName: $disk,
            writerType: Excel::XLSX,
        );

        return $path;
    }

    /**
     * Создаёт лист данных и лист справочников через контейнер.
     *
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            app()->makeWith(
                abstract: NomenclatureDataSheetExport::class,
                parameters: [
                    'typeId' => $this->typeId,
                ],
            ),
            app()->makeWith(
                abstract: NomenclatureReferenceSheetExport::class,
                parameters: [
                    'typeId' => $this->typeId,
                ],
            ),
        ];
    }
}
