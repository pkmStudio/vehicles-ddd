<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Vehicle\Sheets;

use App\Vehicles\Traits\CachesImportFailures;
use App\Vehicles\Traits\HasVehicleImportBaseData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

final class VehicleMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;
    use HasVehicleImportBaseData;

    private int $userId;

    public function __construct(int $userId, string $cacheKey)
    {
        $this->userId = $userId;
        $this->cacheKey = $cacheKey;
        $this->lockKey = "vehicle_import_failures_lock_{$this->userId}";
    }

    /**
     * @throws \Throwable
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            DB::beginTransaction();
            try {
                $this->createOrUpdateVehicle($row->toArray());
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
