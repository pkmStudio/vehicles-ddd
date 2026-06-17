<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Modification;

use App\Vehicles\Enums\BrakeSystemTypeEnum;
use App\Vehicles\Enums\DriveTypeEnum;
use App\Vehicles\Enums\EngineTypeEnum;
use App\Vehicles\Enums\GearTypeEnum;
use App\Vehicles\Models\Modification;
use App\Vehicles\Models\Vehicle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

class ModificationImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow
{
    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            $vehicle = Vehicle::query()->where('ms_id', $row[0])->first();
            Modification::query()->updateOrCreate([
                'mod_id' => $row[1],
                'type' => $row[13],
            ], [
                'vehicle_id' => $vehicle->id,
                'ms_id' => $row[0],
                'year_from' => $row[2],
                'year_to' => $row[3] ?? null,
                'description' => $row[4],
                'power_ps' => $row[5] ?? null,
                'power_kw' => $row[6] ?? null,
                'engine_type' => $row[7] ? EngineTypeEnum::tryFrom($row[7])?->value : null,
                'gear_type' => $row[8] ? GearTypeEnum::tryFrom($row[8])?->value : null,
                'drive_type' => $row[9] ? DriveTypeEnum::tryFrom($row[9])?->value : null,
                'brake_system_type' => $row[10] ? BrakeSystemTypeEnum::tryFrom($row[10])?->value : null,
                'number_of_cylinders' => $row[11] ?? null,
                'capacity_lt' => $row[12] ?? null,
                'details' => null,
                'excel_table_id' => null,
                'localized_name' => null,
            ]);
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Import failure', [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}
