<?php

declare(strict_types=1);

namespace App\Vehicles\Infrastructure\Imports\Engine;

use App\Vehicles\Application\Contracts\Commands\EngineCommandInterface;
use App\Vehicles\Application\Factories\Engine\EngineDataFactory;
use App\Vehicles\Domain\Events\Engine\EngineCommandImported;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Импортер двигателей (приводит базу к виду TD). Строка -> validate -> DTO -> Command.
 * eng_fuel_type валидируется как enum в EngineDataFactory (сырое значение).
 */
class EngineCommandImport implements ShouldQueue, SkipsOnFailure, ToCollection, WithChunkReading, WithEvents, WithStartRow
{
    public function __construct(
        private readonly EngineCommandInterface $command,
        private readonly EngineDataFactory $factory,
    ) {}

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            try {
                $data = $this->factory->make([
                    'eng_id' => $row[0] ?? null,
                    'code_engine' => $row[1] ?? null,
                    'eng_power_kw_start' => $row[2] ?? null,
                    'eng_power_kw_upto' => $row[3] ?? null,
                    'eng_power_ps_start' => $row[4] ?? null,
                    'eng_power_ps_upto' => $row[5] ?? null,
                    'engine_capacity' => $row[6] ?? null,
                    'cylinder_diameter' => $row[7] ?? null,
                    'cylinder_count' => $row[8] ?? null,
                    'eng_number_of_valves' => $row[9] ?? null,
                    'eng_fuel_type' => ($row[10] ?? null) ?: null,
                ]);

                $this->command->upsertByEngId($data);
            } catch (ValidationException $e) {
                $this->onFailure(new Failure($index + $this->startRow(), 'Двигатель', Arr::flatten($e->errors()), $row->toArray()));
            }
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            Log::error('Engine import failure', [
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
