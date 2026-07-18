<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Sheets;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Engine\UpsertEngineFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Engine\Mappers\EngineMainSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер основного листа двигателей (механика): сопоставляет колонки Excel
 * с EngineSheetRowDTO и на каждую строку зовёт обычный сценарий upsert двигателя.
 */
final class EngineMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    public function __construct(
        string $cacheKey,
        string $lockKey,
        private readonly UpsertEngineFromSheetServiceInterface $service,
        private readonly EngineMainSheetRowMapper $rowMapper,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            DB::beginTransaction();
            try {
                $engineRow = $this->rowMapper->map($rowValues);

                $this->service->upsertFromRow($engineRow);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();

                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Двигатели',
                        errors: [$e->getMessage()],
                        values: $rowValues,
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
