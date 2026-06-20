<?php

declare(strict_types=1);

namespace App\Vehicles\Application\Imports\Engine\Sheets;

use App\Vehicles\Domain\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

final class EngineMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    /**
     * Редактируемые колонки: индекс колонки в Excel => поле модели Engine
     * Только эти поля будут обновляться при импорте
     * Для изменения набора редактируемых полей поправить только этот массив
     */
    private const array EDITABLE_COLUMNS = [
        2 => 'engine_capacity',
        4 => 'eng_power_ps_start',
        5 => 'eng_power_ps_upto',
        6 => 'cylinder_count',
        7 => 'cylinder_diameter',
        8 => 'eng_number_of_valves',
    ];

    public function __construct(
        private readonly int $userId,
        string $cacheKey,
        private readonly EngineCommandInterface $command,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = "engine_import_failures_lock_{$userId}";
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            DB::beginTransaction();
            try {
                $data = [];
                foreach (self::EDITABLE_COLUMNS as $columnIndex => $field) {
                    $data[$field] = $row[$columnIndex] ?? null;
                }

                $this->command->updateEditableByEngId((int) $row[0], $data);
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
