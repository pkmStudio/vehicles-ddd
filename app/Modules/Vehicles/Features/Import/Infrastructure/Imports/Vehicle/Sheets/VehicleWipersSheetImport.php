<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Sheets;

use App\Modules\Vehicles\Features\Import\Domain\Contracts\Services\Vehicle\VehicleWiperSpecificationImportServiceInterface;
use App\Modules\Vehicles\Features\Import\Infrastructure\Imports\Vehicle\Mappers\VehicleWiperSheetRowMapper;
use App\Modules\Vehicles\Features\Import\Infrastructure\Traits\CachesImportFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Excel-адаптер листа «дворники»: маппит строку в DTO и передаёт в сервис импорта.
 */
final class VehicleWipersSheetImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithStartRow
{
    use CachesImportFailures;

    public function __construct(
        string $cacheKey,
        string $lockKey,
        private readonly VehicleWiperSpecificationImportServiceInterface $upsertWiperSpec,
        private readonly VehicleWiperSheetRowMapper $rowMapper,
    ) {
        $this->cacheKey = $cacheKey;
        $this->lockKey = $lockKey;
    }

    /**
     * @throws \Throwable
     */
    public function collection(Collection $collection): void
    {
        $trimString = fn ($value) => is_string($value) ? trim($value) : $value;

        foreach ($collection as $indexRow => $row) {
            $row = $row->map($trimString);
            $rowValues = $row->toArray();
            try {
                $vehicleRow = $this->rowMapper->map($rowValues);

                $upsertWiperSpec = fn () => $this->upsertWiperSpec->upsertFromRow($vehicleRow);

                DB::transaction($upsertWiperSpec);
            } catch (\Throwable $e) {
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
}
