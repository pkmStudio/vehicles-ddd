<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Engine\Sheets;

use App\Vehicles\Import\Domain\Contracts\Services\Engine\EngineEditableColumnsMapperInterface;
use App\Vehicles\Import\Domain\Contracts\Services\Engine\UpdateEngineEditableFieldsServiceInterface;
use App\Vehicles\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер edit-листа двигателей (механика): сопоставляет колонки Excel редактируемым
 * полям и на каждую строку зовёт сценарий частичного обновления.
 */
final class EngineMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    public function __construct(
        string $runId,
        string $cacheKey,
        private readonly UpdateEngineEditableFieldsServiceInterface $service,
        private readonly EngineEditableColumnsMapperInterface $editableColumnsMapper,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = "engine_import_failures_lock_{$runId}";
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            DB::beginTransaction();
            try {
                $attributes = $this->editableColumnsMapper->extractEditableAttributes($row->toArray());

                $this->service->updateEditableFields((int) $row[0], $attributes);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();

                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Двигатели',
                        errors: [$e->getMessage()],
                        values: $row->toArray(),
                    )
                );
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}
