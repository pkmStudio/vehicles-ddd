<?php

declare(strict_types=1);

namespace App\Modules\Applicability\Features\Export\Infrastructure\Exports;

use App\Modules\Applicability\Features\Export\Domain\Contracts\Exports\ModificationKitApplicabilityExportInterface;
use App\Modules\Applicability\Features\Export\Domain\Contracts\Services\ModificationKitApplicabilityExportServiceInterface;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ExportRunContextDTO;
use App\Modules\Applicability\Features\Export\Domain\DTOs\ModificationKitApplicabilityRowDTO;
use App\Modules\Applicability\Features\Export\Infrastructure\Exports\Sheets\ModificationKitApplicabilitySheetExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

final readonly class ModificationKitApplicabilityExport implements ModificationKitApplicabilityExportInterface, WithMultipleSheets
{
    private const string BRAKE_PAD_TYPE = 'BP';

    private const string OIL_FILTER_TYPE = 'OF';

    private const string AIR_FILTER_TYPE = 'AF';

    /**
     * Получает service строк export-а применяемости к модификациям.
     */
    public function __construct(
        private ModificationKitApplicabilityExportServiceInterface $service,
    ) {}

    /**
     * Сохраняет XLSX-файл применяемости к модификациям.
     */
    public function export(ExportRunContextDTO $context, ?string $disk = null): string
    {
        $disk ??= (string) config('applicability.export.output.disk', 'local');
        $directory = (string) config('applicability.export.output.directory', 'exports');
        $path = "{$directory}/applicability-modifications-{$context->operationId}.xlsx";

        ExcelFacade::store(
            export: $this,
            filePath: $path,
            diskName: $disk,
            writerType: Excel::XLSX,
        );

        return $path;
    }

    /**
     * Возвращает листы в формате, который принимает импорт `kit_applicability`.
     *
     * @return array<int, WithTitle>
     */
    public function sheets(): array
    {
        $rows = $this->service->getRows();
        $this->warnUnassignedRows($rows);

        return [
            new ModificationKitApplicabilitySheetExport(
                service: $this->service,
                title: 'Колодки',
                rows: $this->rowsForType($rows, self::BRAKE_PAD_TYPE),
            ),
            new ModificationKitApplicabilitySheetExport(
                service: $this->service,
                title: 'Масляные фильтры',
                rows: $this->rowsForType($rows, self::OIL_FILTER_TYPE),
            ),
            new ModificationKitApplicabilitySheetExport(
                service: $this->service,
                title: 'Воздушные фильтры',
                rows: $this->rowsForType($rows, self::AIR_FILTER_TYPE),
            ),
        ];
    }

    /**
     * Возвращает строки только для одного типа набора.
     *
     * @param  Collection<int, ModificationKitApplicabilityRowDTO>  $rows
     * @return Collection<int, ModificationKitApplicabilityRowDTO>
     */
    private function rowsForType(Collection $rows, string $typeChar): Collection
    {
        return $rows
            ->filter(static fn (ModificationKitApplicabilityRowDTO $row): bool => $row->typeChar === $typeChar)
            ->values();
    }

    /**
     * Возвращает поддерживаемые типы листов.
     *
     * @return list<string>
     */
    private function knownTypeChars(): array
    {
        return [
            self::BRAKE_PAD_TYPE,
            self::OIL_FILTER_TYPE,
            self::AIR_FILTER_TYPE,
        ];
    }

    /**
     * Логирует строки, которые нельзя безопасно назначить ни одному листу.
     *
     * @param  Collection<int, ModificationKitApplicabilityRowDTO>  $rows
     */
    private function warnUnassignedRows(Collection $rows): void
    {
        $knownTypeChars = $this->knownTypeChars();

        foreach ($rows as $row) {
            if (in_array($row->typeChar, $knownTypeChars, true)) {
                continue;
            }

            Log::warning('Applicability modification export skipped row with unknown kit type', [
                'kit_id' => $row->kitId,
                'ms_id' => $row->msId,
                'mod_id' => $row->modId,
                'type_char' => $row->typeChar,
            ]);
        }
    }
}
