<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\UpsertVehicleFromSheetServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

final class VehicleMainSheetImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    public function __construct(
        string $cacheKey,
        string $lockKey,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    public function collection(Collection $collection): void
    {
        $upsertVehicle = $this->upsertVehicle();
        $rowMapper = $this->rowMapper();

        foreach ($collection as $indexRow => $row) {
            $rowValues = $row->toArray();
            try {
                DB::transaction(function () use ($rowMapper, $rowValues, $upsertVehicle): void {
                    $vehicleRow = $rowMapper->map($rowValues);

                    $upsertVehicle->upsertFromRow($vehicleRow);
                });
            } catch (ImportRowValidationException $e) {
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

    private function upsertVehicle(): UpsertVehicleFromSheetServiceInterface
    {
        return app(UpsertVehicleFromSheetServiceInterface::class);
    }

    private function rowMapper(): VehicleSheetRowMapper
    {
        return app(VehicleSheetRowMapper::class);
    }
}
