<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Engine;

use App\Vehicles\Enums\EngineFuelTypeEnum;
use App\Vehicles\Events\EngineCommandImported;
use App\Vehicles\Models\Engine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Импортер, который запускается из консольной команды и приводит базу к виду TD
 */
class EngineCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            Engine::query()->updateOrCreate([
                'eng_id' => $row[0],
            ], [
                'code_engine' => $row[1],
                'eng_power_kw_start' => $row[2] ?? null,
                'eng_power_kw_upto' => $row[3] ?? null,
                'eng_power_ps_start' => $row[4] ?? null,
                'eng_power_ps_upto' => $row[5] ?? null,
                'engine_capacity' => $row[6] ?? null,
                'cylinder_diameter' => $row[7] ?? null,
                'cylinder_count' => $row[8] ?? null,
                'eng_number_of_valves' => $row[9] ?? null,
                'eng_fuel_type' => $row[10] ? EngineFuelTypeEnum::tryFrom($row[10])?->value : null,
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

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                event(new EngineCommandImported);
            },
        ];
    }
}
