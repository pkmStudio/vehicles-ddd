<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Vehicle\Sheets;

use App\Vehicles\Domain\Contracts\Application\Import\UseCases\Vehicle\UpsertVehicleFromSheetUseCaseInterface;
use App\Vehicles\Domain\Contracts\Infrastructure\Imports\Sheets\VehicleMainSheetImportInterface;
use App\Vehicles\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

final class VehicleMainSheetImport implements SkipsOnFailure, ToCollection, WithStartRow, VehicleMainSheetImportInterface
{
    use CachesImportFailures;

    public function __construct(
        private readonly int $userId,
        string $cacheKey,
        private readonly UpsertVehicleFromSheetUseCaseInterface $upsertVehicle,
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
