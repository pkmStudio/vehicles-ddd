<?php

declare(strict_types=1);

namespace App\Vehicles\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Vehicles\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Vehicles\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleSheetRowMapper;
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
        string $cacheKey,
        string $lockKey,
        private readonly UpsertVehicleFromSheetServiceInterface $upsertVehicle,
        private readonly VehicleSheetRowMapper $rowMapper,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    /**
     * @throws \Throwable
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            DB::beginTransaction();
            try {
                $vehicleRow = $this->rowMapper->map($rowValues);

                $this->upsertVehicle->upsertFromRow($vehicleRow);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();

                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Основная информация',
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
