<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Modules\Templates\Domain\Exceptions\DetailsDataBuildException;
use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Modules\Vehicles\Features\Import\Domain\Exceptions\ImportRowValidationException;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleWiperSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер листа «дворники»: маппит строку в DTO и передаёт в сервис импорта.
 */
final class VehicleWipersSheetImport implements ShouldQueue, SkipsEmptyRows, SkipsOnFailure, ToCollection, WithStartRow
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
        $rowMapper = $this->rowMapper();
        $upsertWiperSpec = $this->upsertWiperSpec();
        $trimString = fn ($value) => is_string($value) ? trim($value) : $value;

        foreach ($collection as $indexRow => $row) {
            $row = $row->map($trimString);
            $rowValues = $row->toArray();
            try {
                $vehicleRow = $rowMapper->map($rowValues);

                $upsertWiperSpecCallback = fn () => $upsertWiperSpec->upsertFromRow($vehicleRow);

                DB::transaction($upsertWiperSpecCallback);
            } catch (ImportRowValidationException|DetailsDataBuildException $e) {
                $this->onFailure(
                    new Failure(
                        row: $indexRow + $this->startRow(),
                        attribute: 'Дворники',
                        errors: [$e->getMessage()],
                        values: $rowValues,
                    ),
                );
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }

    private function rowMapper(): VehicleWiperSheetRowMapper
    {
        return app(VehicleWiperSheetRowMapper::class);
    }

    private function upsertWiperSpec(): VehicleWiperSpecificationImportServiceInterface
    {
        return app(VehicleWiperSpecificationImportServiceInterface::class);
    }
}
