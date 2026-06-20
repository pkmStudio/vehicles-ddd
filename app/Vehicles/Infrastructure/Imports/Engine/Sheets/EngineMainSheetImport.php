<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine\Sheets;

use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Engine\UpdateEngineEditableFieldsUseCaseInterface;
use App\Vehicles\Domain\Contracts\Application\Import\Support\EngineMainSheetImportServiceInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\Sheets\EngineMainSheetImportInterface;
use App\Vehicles\Traits\CachesImportFailures;
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
final class EngineMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow, EngineMainSheetImportInterface
{
    use CachesImportFailures;

    public function __construct(
        private readonly int $userId,
        string $cacheKey,
        private readonly UpdateEngineEditableFieldsUseCaseInterface $useCase,
        private readonly EngineMainSheetImportServiceInterface $engineMainSheetImportService,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = "engine_import_failures_lock_{$userId}";
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            DB::beginTransaction();
            try {
                $attributes = $this->engineMainSheetImportService->extractEditableAttributes($row->toArray());

                $this->useCase->execute((int) $row[0], $attributes);
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
