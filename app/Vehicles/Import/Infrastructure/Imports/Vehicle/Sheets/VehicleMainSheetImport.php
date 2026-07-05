<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Vehicles\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

final class VehicleMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    public function __construct(
        string $runId,
        string $cacheKey,
        private readonly UpsertVehicleFromSheetServiceInterface $upsertVehicle,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = "vehicle_import_failures_lock_{$runId}";
    }

    /**
     * @throws \Throwable
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            DB::beginTransaction();
            try {
                $this->upsertVehicle->execute($row->toArray());
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();

                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Основная информация',
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
