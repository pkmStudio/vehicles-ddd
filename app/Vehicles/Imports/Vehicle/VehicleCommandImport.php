<?php

declare(strict_types=1);

namespace App\Vehicles\Imports\Vehicle;

use App\Vehicles\Enums\CarcaseTypeEnum;
use App\Vehicles\Enums\SteeringTypeEnum;
use App\Vehicles\Events\VehicleCommandImported;
use App\Vehicles\Models\Manufacturer;
use App\Vehicles\Models\Vehicle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
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
class VehicleCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithStartRow, WithEvents
{
    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $indexRow => $row) {
            try {
                $ms_id = $row[1];
                $manufacturerId = Manufacturer::query()->where('mfa_id', $row[0])->first()->id;

                Vehicle::query()->updateOrCreate([
                    'ms_id' => $ms_id,
                ], [
                    'parent_id' => null,
                    'manufacturer_id' => $manufacturerId,
                    'mfa_id' => $row[0],
                    'ms_id' => $ms_id,
                    'name' => $row[2],
                    'generation' => $row[3],
                    'type' => $row[7],
                    'type_carcase' => $row[4] ? CarcaseTypeEnum::tryFrom($row[4])?->value : null,
                    'provider' => 'TD',
                    'generation_year_from' => $row[5],
                    'generation_year_to' => $row[6] ?? null,
                    'is_allow' => false,
                    'localized_name' => null,
                    'excel_table_id' => null,
                    'generation_short' => null,
                    'steering_type' => SteeringTypeEnum::LEFT->value,
                ]);
            } catch (\Exception $exception) {
                $this->onFailure(new Failure(
                    row: $indexRow + $this->startRow(),
                    attribute: '',
                    errors: [$exception->getMessage()],
                    values: [$row],
                ));
            }
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            \Log::error('Import failure', [
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
                event(new VehicleCommandImported());
            },
        ];
    }
}
