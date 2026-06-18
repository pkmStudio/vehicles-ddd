<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Vehicle\Sheets;

use App\Vehicles\Commands\Vehicle\VehicleCommandInterface;
use App\Vehicles\Traits\CachesImportFailures;
use App\Vehicles\Traits\HasVehicleImportBaseData;
use App\Vehicles\Validators\Vehicle\VehicleValidator;
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

    public function __construct(
        private readonly int $userId,
        string $cacheKey,
        private readonly VehicleCommandInterface $vehicleCommand,
        private readonly VehicleValidator $vehicleValidator,
    ) {
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
